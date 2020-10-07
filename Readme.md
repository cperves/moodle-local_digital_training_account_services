# digital_training_account_services plugin
## Usage
This plugin return moodle usage datas for student in moodle
## Install
### file installation
* extract this plugin into moodle local directory
* run moodle update
### dependencies
install also dependant plugins
* logstore_last_viewed_course_module [see]()
  * a log store plugin enabling filtering usefull log data in one table
* logstore_last_updated_course_module [see]()
  * a log store plugin enabling filtering usefull log data in one table
* local_metadata : forked unistra version [see]()
  * adding metadatas to moodle context for POC use
  * forked for issue resolving technical troubles 
* local_metadatatools [see]()
  * a library to use local metadata as objects
* local_user_identity_checker [see]()
  * a JWT tool enabling a user to give his autorization to comunicate with a moodle instance transmitting its username
### Install WS and meta datas
#### WS
from a command line
```shell script
sudo -u www-data /usr/bin/php /var/www/moodle/local/digital_training_account_services/cli/install_ws.php
```
generate a token for digital_training_account_services_user user and et digital_training_account_services_user web service
#### metadatas
```shell script
sudo -u www-data /usr/bin/php /var/www/moodle/local/metadata_tools/cli/add_metadata_category.php --category=poc --contextlevel=70 --sortorder=0
# keep the returned id for next command
sudo -u www-data /usr/bin/php /var/www/moodle/local/metadata_tools/cli/add_metadata_field.php --category=previous_categoryid --field="eolepositioning" --dname="Test de Positionnement" --contextlevel=70 --datatype=checkbox --sortorder=0
sudo -u www-data /usr/bin/php /var/www/moodle/local/metadata_tools/cli/add_metadata_field.php --category=previous_categoryid --field="eoleinstitution" --name=Composante --contextlevel=70 --datatype=text --sortorder=0
sudo -u www-data /usr/bin/php /var/www/moodle/local/metadata_tools/cli/add_metadata_field.php --category=previous_categoryid --field="eoledomain" --dname=Matière --contextlevel=70 --datatype=menu --sortorder=0 --param1="Mathématiques\r\nLittérature\r\nBiologie\r\nInformatique\r\nHistoire"
sudo -u www-data /usr/bin/php /var/www/moodle/local/metadata_tools/cli/add_metadata_field.php --category=previous_categoryid --field="eolelevel" --dname=Niveau --contextlevel=70 --datatype=menu --sortorder=0 --param1="L1\r\nL2\r\nL3\r\nM1\r\nM2"
# TODO defaults value WIP
```
* Don't forget to activate metadatas for module plugins from moodle administration 


