# scrab-miner
Scrabble web-ui from miner's devices and collect in one page

Get statistics from a host. May be 4 miners type:
    0 - unsupported device
    1 - antminer hwv 1.0.0.6 or 1.0.1.3 or 16.8.1.3
    2 - antminer hwv 1.0.0.9
    3 - innosilicon
    Return string with parameters

# screenshots

Index page(Red color - device shutdown, Yellow - device with errors):
![Alt text](/screenshot/index.png)

Index page(innosilicon):
![Alt text](/screenshot/index2.png)

Device add page:
![Alt text](/screenshot/device_add.png)

Group add page:
![Alt text](/screenshot/group_add.png)

# Requirements

sqlite 3
python 2.7 and over
php 7
apache/nginx

# Installation

<b>grub.py</b> - you can to run this file via python3 in crontab

<b>index.php</b> - index page

<b>edit.php</b> - edit devices and groups

<b>folder "js"</b> - jQuery

You can to move this files into root-folder of WebServer and run grub.py. After this create file "miners.db" and you open a web-browser http://localhost and can to add devices. Add grub.py into crontab.

# P.S.
Suggestions or thanks go to 
email: develop@romanov.one
