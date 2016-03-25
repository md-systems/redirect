# Add needed dependencies.
cd "$DRUPAL_TI_DRUPAL_DIR"
pwd
composer install
ln -sf $(pwd) subdir
