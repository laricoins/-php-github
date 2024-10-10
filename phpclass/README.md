
тут куски команд для линукса как его дружить с крипто про






добрый ... я с танцами установил на линукс сервер крипто про, + php расширение

счас жду как буг освободиться чтобы перенести ключи


/opt/cprocsp/sbin/amd64/cryptcp -restorekeyset -cont '\\.\HDIMAGE\wildberriesfulprint' -dest '/home/wildberries/cps/fulprint/fullprint.p7b'


/opt/cprocsp/bin/amd64/csptestf -keyset -import -cont '\\.\HDIMAGE\wildberriesfulprint' -dest '/home/wildberries/cps/fulprint/fullprint.p7b'



/opt/cprocsp/bin/amd64/csptestf -keyset -import -cont '\\.\HDIMAGE\wildberriesfulprint' -file '/home/wildberries/cps/fulprint/fullprint.p7b'



/opt/cprocsp/bin/amd64/csptest -keyset -enum


/opt/cprocsp/sbin/amd64/cpconfig -restore -keyset /home/wildberries/cps/fulprint/fullprint.p7b


./cspmigr -restore -cont '\\.\HDIMAGE\wildberriesfulprint' -in '/home/wildberries/cps/fulprint/fullprint.p7b'



 /opt/cprocsp/bin/amd64/certmgr -inst -all -store uMy -file /home/wildberries/cps/fulprint/fullprint.p7b
 
 /opt/cprocsp/bin/amd64/certmgr -inst  -store uMy -file /home/wildberries/cps/fulprint/fp.cer  -cont '\\.\HDIMAGE\МБ Чичеров'
 
 
 /opt/cprocsp/sbin/amd64/ccpconfig -hardware reader -add wildberries store


/opt/cprocsp/bin/amd64/csptest -keyset -check -cont '\\.\HDIMAGE\3584.000\16DE'



/opt/cprocsp/bin/amd64/certmgr -inst -store uMy -file /home/wildberries/cps/fulprint/fp.cer

/opt/cprocsp/bin/amd64/certmgr -delete -store uMy


/opt/cprocsp/bin/amd64/certmgr -list -store uMy



/opt/cprocsp/bin/amd64/csptest -keyset -enum_cont -verifycontext -fqcn



/opt/cprocsp/bin/amd64/csptest -keyset -enum_cont -fqcn -verifyc



/opt/cprocsp/bin/amd64/certmgr -inst -cont '\\3584.000\16DE' -file /home/wildberries/cps/fulprint/fp.cer


/opt/cprocsp/bin/amd64/csptestf -absorb -certs

/opt/cprocsp/bin/amd64/certmgr -inst  uMy -cont '\\.\HDIMAGE\\3584.000\16DE' -file /home/wildberries/cps/fulprint/fp.cer



/opt/cprocsp/bin/amd64/certmgr -inst -store uroot -file /home/wildberries/cps/rootca.cer
/opt/cprocsp/bin/amd64/certmgr -inst -store uroot -file /home/wildberries/cps/subca.cer



sudo -u wildberries /opt/cprocsp/bin/amd64/csptest -absorb -certs -autoprov



dd if=/dev/zero of=/opt/usb/usb_image.img bs=1M count=128

mkfs.vfat /opt/usb/usb_image.img
sudo chmod 777 /media/usb

sudo mount -o loop /opt/usb/usb_image.img /mnt/usb
sudo losetup /dev/loop0 /opt/usb/usb_image.img


chmod 777 /opt/usb/usb_image.img

mkdir /media/usb
mount -o loop,umask=000 /opt/usb/usb_image.img /media/usb
umount  /media/virtual_usb


*********************************
sudo mkdir -p /opt/usb
sudo dd if=/dev/zero of=/opt/usb/usb_image.img bs=1M count=100
sudo losetup /dev/loop0 /opt/usb/usb_image.img
sudo mkfs.vfat /dev/loop0
sudo mkdir -p /media/virtual_usb
sudo chmod 777 /media/virtual_usb
sudo mount -o loop,umask=000 /opt/usb/usb_image.img /media/virtual_usb
sudo ln -s /dev/loop0 /dev/disk/by-path/virtual-usb

ls -l /dev/disk/by-path/*usb*




/opt/cprocsp/bin/amd64/csptest -keys -check -cont '\\.\C71C-511D\ФП Чичеров' -info


/opt/cprocsp/bin/amd64/certmgr -inst -cont '\\.\C71C-511D\ФП Чичеров'







./install.sh lsb-cprocsp-devel cprocsp-pki-cades









/opt/cprocsp/sbin/amd64/cpconfig -ini '\cryptography\apppath' -add string 'libcurl.so' '/lib/x86_64-linux-gnu/libcurl.so.4'






/lib/x86_64-linux-gnu/libcurl.so.4




openssl pkcs7 -print_certs -in /install/m0.p7b -out /usr/local/share/ca-certificates/m0.crt
openssl pkcs7 -print_certs -in m0.p7b -out m0.crt


openssl x509 -inform DER -in /install/m0.p7b -out /usr/local/share/ca-certificates/m0.pem -outform PEM

