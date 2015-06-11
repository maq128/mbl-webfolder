#!/bin/bash

PATH=/sbin:/bin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin

echo red > /sys/class/leds/a3g_led/color
echo yes > /sys/class/leds/a3g_led/blink

echo

#sets $image_img
image_img="/DataVolume/shares/Public/rootfs.img"
echo

# Sort out what MD device is what
currentRootDevice=`cat /proc/cmdline | awk '/root/ { print $1 }' | cut -d= -f2`
if [ "${currentRootDevice}" = "/dev/md0" ]; then
    upgradeRootDevice="/dev/md1"
elif [ "${currentRootDevice}" = "/dev/md1" ]; then
    upgradeRootDevice="/dev/md0"
else
    echo "Unknown rootfs boot device: '${currentRootDevice}', exiting."
    exit 1
fi

echo "currentRootDevice = ${currentRootDevice}"
echo "upgradeRootDevice = ${upgradeRootDevice}"
echo

# If the upgrade MD device is used, shut it down
if [ -e $upgradeRootDevice ]; then
    echo "stopping upgrade md device ${upgradeRootDevice}"
    echo
    mdadm --stop $upgradeRootDevice
    mdadm --wait $upgradeRootDevice
    sleep 1
fi

echo "Ensure both partitions are members of the original MD device"
# "--remove" only remove failed disks and "--add" them causes resyncing
mdadm ${currentRootDevice} --remove /dev/sda1 #> /dev/null 2>&1
mdadm ${currentRootDevice} --add /dev/sda1    #> /dev/null 2>&1
mdadm --wait ${currentRootDevice}
mdadm ${currentRootDevice} --remove /dev/sda2 #> /dev/null 2>&1
mdadm ${currentRootDevice} --add /dev/sda2    #> /dev/null 2>&1
mdadm --wait ${currentRootDevice}
sleep 1

echo
echo "Setting up the upgraded raid unit"
sync
mdadm --wait ${currentRootDevice}
mdadm ${currentRootDevice} -f /dev/sda1 -r /dev/sda1 2> /dev/null > /dev/null
mdadm --wait ${currentRootDevice}
sleep 1
mdadm --zero-superblock --force --verbose /dev/sda1
mdadm --create ${upgradeRootDevice} --verbose --metadata=0.9 --raid-devices=2 --level=raid1 --run /dev/sda1 missing
mdadm --wait ${upgradeRootDevice}
sleep 1
sync
mkfs.ext3 -c -b 4096 ${upgradeRootDevice}
sync
echo

# installing new image on update device
# img file was searched for by ./findImage.sh
echo "Copy image to upgrade device ${upgradeRootDevice}"
dd if=${image_img} of=${upgradeRootDevice}
echo

# new OS was accepted
mkdir -p /mnt/rootfs
mount ${upgradeRootDevice} /mnt/rootfs

#needed
touch /mnt/rootfs/etc/.updateInProgress
chmod 777 /mnt/rootfs/etc/.updateInProgress

#enable ssh
echo "enabled" > /mnt/rootfs/etc/nas/service_startup/ssh

# copy uboot script too boot directory
if [ ${upgradeRootDevice} == "/dev/md0" ]; then
    cp /mnt/rootfs/usr/local/share/bootmd0.scr /mnt/rootfs/boot/boot.scr
else
    cp /mnt/rootfs/usr/local/share/bootmd1.scr /mnt/rootfs/boot/boot.scr
fi

# some safety since it is a critical step here
sync
sleep 2
umount /mnt/rootfs
sleep 2
sync
echo

# ensures reboot
echo no     > /sys/class/leds/a3g_led/blink
echo yellow > /sys/class/leds/a3g_led/color
echo "all done, now rebooting"
shutdown -r 0