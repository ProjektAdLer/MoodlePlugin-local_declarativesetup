# Declarative setup #

# Deklarative Einrichtung #

Ermöglicht die Konfiguration von Moodle auf deklarative Weise, ähnlich wie Ansible.

## Kompabilität
Folgende Versionen werden unterstützt (mit mariadb und postresql getestet):

siehe [plugin_compatibility.json](plugin_compatibility.json)

## Verwendung ##
`config.php` muss schreibbar sein, damit einige Plays funktionieren.

Siehe [Playbook README.md](playbook/README.md) für weitere Informationen zur Nutzung dieses Plugins.

## Installation über hochgeladene ZIP-Datei ##

1. Melden Sie sich als Administrator auf Ihrer Moodle-Seite an und gehen Sie zu
   _Website-Administration > Plugins > Plugins installieren_.
2. Laden Sie die ZIP-Datei mit dem Plugin-Code hoch. Zusätzliche Angaben sollten
   nur erforderlich sein, wenn der Plugin-Typ nicht automatisch erkannt wird.
3. Überprüfen Sie den Plugin-Validierungsbericht und schließen Sie die Installation ab.

## Manuelle Installation ##

Das Plugin kann auch installiert werden, indem der Inhalt dieses Verzeichnisses in

    {your/moodle/dirroot}/local/declarativesetup

kopiert wird.

Danach melden Sie sich als Administrator auf Ihrer Moodle-Seite an und gehen Sie zu
_Website-Administration > Mitteilungen_, um die Installation abzuschließen.

Alternativ können Sie den folgenden Befehl ausführen:

    $ php admin/cli/upgrade.php

um die Installation über die Kommandozeile abzuschließen.
