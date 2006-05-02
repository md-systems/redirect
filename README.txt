Readme
------

This module allows you to specify a redirect url for a node. If the redirect
url is set, the node redirects rather than displaying node content. Likely 
use cases include the following:

  * You'd like an item to appear in a book outline but to redirect to
    external content.

  * You need temporarily (or permanently) to redirect a node to some other
    page (local or external).



Usage
-----

Once installed, this module adds a text field to the node creation/editing
form for all nodes. Simply enter a url in the field to enable redirection. To
disable, empty the text field.



Requirements
------------

This module has been tested on Drupal 4.6 and includes a database defintion
for mysql only.



Installation
------------

1. Create the SQL tables. This depends a little on your system, but the most
   common method is:
        mysql -u username -ppassword drupal < redirect.mysql

2. Copy redirect.module to the Drupal modules/ directory.

3. Enable redirect in the "site settings | modules" administration screen.

