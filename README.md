# AdLer setup #

TODO Describe the plugin shortly here.

TODO Provide more detailed description here.

# Requirements
- Maintenance mode should be enabled
- Allow providing GitHub authentication


- Plugins
  - input: list of git_project and version 
  - desired state: equal to input
    - if version is below the desired state or plugin is not installed, update to desired state
    - if version is above the desired state, fail
    - if version is equal to the desired state, do nothing
  - output: list of git_project and version
- Roles
  - input: role name and list of capabilities and context where the role can be assigned
  - desired state: equal to input
    - if role is not present, create it
    - if role is present, and capabilities are not equal, update capabilities
    - if role is present, and context is not equal, update context
  - output: role name and list of capabilities and context where the role can be assigned
- web services
  - input: enabled, protocol_rest enable
  - desired state: equal to input
    - if web service enabled state does not match "enabled", update it
    - if protocol_rest enabled state does not match "protocol_rest enabled", update it
  - output: enabled, protocol_rest enabled
- role capabilities
  - input: role name and capability with state (allow, prohibit, prevent)
  - desired state: equal to input
    - if capability state does not match desired state, update it
  - output: role name and all capability with state (allow, prohibit, prevent) 
- User
  - input: name, password, first name (optional), last name (optional), email (optional), role (optional), create adler course category (default false, requires local_adler plugin)
  - desired state: equal to input
    - if user is not present, create it
    - if user is present, and password is not equal, update password
    - if user is present, and first name is not equal, update first name
    - if user is present, and last name is not equal, update last name
    - if user is present, and email is not equal, update email
    - if user is present, and role is not equal, update role
  - output: user with all relevant information
- Language pack
  - input: list of language packs with state (installed, not installed)
  - desired state: equal to input
    - if language pack state does not match desired state, update it
  - output: list of all language packs with state (installed, not installed)


## plugin install
input
  - github_project
  - version
  - moodle_name
1) check update required
   1) plugin not yet installed -> yes
   2) plugin installed, desired version is release, installed version is below desired state -> yes
   3) plugin installed, desired version is branch
      1) get technical (eg 2024101000) version number of desired state from github (version.php)
      2) check technical version number of installed plugin 
         - is below or equal to desired version -> yes
         - throw exception
   4) plugin installed, desired version is equal to installed version -> do nothing
   5) plugin installed, desired version is above installed version -> throw exception
2) install/update plugins
    - plugin_manager->install_plugins


## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/adlersetup

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2024 Markus Heck (Projekt Adler) <markus.heck@hs-kempten.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
