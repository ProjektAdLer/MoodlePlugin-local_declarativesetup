# Playbooks

This directory contains playbooks for the AdLer setup. New playbooks can be added by creating a plugin of type `playbook`.
The playbooks can be executed via the `run_playbook.php` cli script.

# Create new playbook

The `sample` playbook represents a minimal playbook which can be used as a reference for new playbooks.
Each playbook plugin has to have the following files:

- `version.php`
- `lang/en/playbook_<playbook name>.php`
- `classes/playbook.php`

Additional files/folders can be added if needed. For example:

- `classes/local/plays/<play name>/`: For own plays, see section [Creating new plays](#creating-new-plays)
- `tests/`: For tests, see section [Testing](#testing)
  - `composer.json`: Can be added to install additional dependencies for testing like Mockery
- assets: For assets like custom logos. Currently, there is no predefined location for these files.

## version.php

This file is the same as for all other plugins. Use the below template:

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'playbook_<playbook name>';
$plugin->release = '0.1.0';
$plugin->version = 2024121000;
$plugin->requires = 2024042200;
$plugin->maturity = MATURITY_ALPHA;
```

## lang/en/playbook_<playbook name>.php

A single translation has to be defined. It is the name that is shown in the moodle (admin) UI e.g. in the list of 
installed plugins. Use the below template:

```php
<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = '<Human readable name of your playbook>';
```

## classes/playbook.php

This file will contain your actual playbook implementation. A playbook consists of a list of "plays" which are executed
one after another. The concept is similar to a playbook in Ansible. See a playbook as a config file representing the
desired state of the system. The code should be kept as simple as possible. Do not do too complex logic and avoid
communicating with moodle or other systems if possible. Only interact with plays and only do what Ansible would allow
you to do. Everything else is considered bad practice and not supported usage.
If the existing plays are not sufficient for your needs, you can create new plays. See section [Creating new plays](#creating-new-plays).

A typical playbook looks like this:

```php
<?php
namespace playbook_<your playbook name>;

class playbook {
    public function __construct() {
        $play_model = new <play model>(<data>);
        $play_model->attribute = 'value';  // every attribute of default plays can be set through the constructor, but for
                                           // esp. for optional parameters, it is also ok setting them this way
        $play = new <play name>($play_model);
        $play->play();
        
        $play = new <play name>(new <play model>(<data>));
        $play->play();
        
        // ...
    }
}
```

All relevant code should be placed directly in the constructor. No other method will be called.

If a play fails it will throw an exception. Everything else is considered a success. It is ok to catch an exception to
react on it (like stay in maintenance mode, calling an external API to send a notification might also be an acceptable 
exception to the "don't use anything except plays" rule). But in general it's totally to not catch exceptions that 
cannot be handled properly and let the execution fail with an exception.


### Creating new plays
Currently, the main plugin does not support a sub plugin "play", but it is possible to create plays in playbook plugins.
Create a folder `<your playbook>/classes/local/plays/<name of your play>`. In that folder you can create your new play.
Follow these conventions (like the existing plays):

- Create a `models` folder and inside it a file `<your play name>_model.php`. This model will be used as input of your play
```php
<?php
namespace playbook_<your playbook name>\local\play\config\models;

class <your play name>_model {
    public string $config_name;

    // additional properties

    public function __construct(string $config_name) {
        $this->config_name = $config_name;
        
    }
}
```
Further models can also be placed in this folder.

- If you define custom exceptions belonging to your play, create a folder `exceptions` and place your exceptions there.
- Create a file `<your play name>.php` directly in your play folder. This file will contain the code of your play.
```php
<?php
namespace playbook_<your playbook name>\local\play\<your play name>\models;
use local_declarativesetup\local\play\base_play;


/**
 * @property <your play name>_model[] $input
 */
class <your play name> extends base_play {
    /**
     * This play takes a list of {@link <your play name>_model} and ensures ... (describe your play here)
     *
     * {@link get_output} describe your plays output here, if implemented
     *
     * @param <your play name>_model[] $input
     */
    public function __construct(array $input) {
        parent::__construct($input);
    }

    /**
    * @return bool Returns true if state changed, false if nothing changed
    */
    protected function play_implementation(): bool {...}
}
```

The input variable can either be an array of your model or a single instance of your model. Optionally 
`protected function get_output_implementation(): array {...}` can be implemented to return the output of your play.

If something goes wrong and the error cannot be handled by the play, don't catch the exception (and don't do stupid returns).

Except for `base_play`, this plugin does not provide stable interfaces to be used by other plugins. In case you need
to modify config.php (forced settings) you may use `config_manager`. Although it's API might change in the future, it's
still better than having another hacky implementation.

### Testing

For very simple playbooks testing can be considered as not necessary. See it as config file, you would not check that
a config file contains exactly the content it contains. Equally, a test for a playbook would be a 1:1 replication of
each line of the playbooks code and therefore directly bound to the implementation.

Integration testing can be highly complex. Some plays do change the filesystem content, which will not be rolled back
by moodle. Every subsequent test would not have the same preconditions anymore.

Most realistic test would be creating a fresh moodle instance, running the playbook and then running API or behat tests
to check if the system works as desired.

A sample test using Mockery can be found in the `sample` playbook. As these tests use Mockery, `composer install` 
(containing Mockery as dev dependency) has to be run before running the tests.

If the playbook contains more complex logic (which is not recommended) or implements custom plays, tests should be
written for these parts.
