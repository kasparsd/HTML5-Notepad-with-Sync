HTML5 Notepad App
by Kaspars Dambis (http://konstruktors.com)
Fork by Benoît HERVIER (http://khertan.net)

Fork addition :
============
*  Added support for devices without accurate time set, by adding a time delta to
the synchronization process.
*  Created a QML Harmattan client for use on Nokia n950 and Nokia n9

Planned feature :
============
* Multi account support

Installation
============

1. Upload files to your server
2. Rename 'entries' folder to something random and harder to guess
3. Make that folder writable (CHMOD 0777)
4. Edit sync.php and specify your new DATA_DIR (the one you just renamed)
5. In sync.php change username and password to something unique
6. Done!

