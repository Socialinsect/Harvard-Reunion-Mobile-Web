#!/bin/bash

# This is for creating subdirectories on the dev server with different
# copies of the Harvard Reunions repo

SUB_DIR=$1
DEPLOY_DIR=/opt/haareunions/$SUB_DIR
REPO_NAME=Harvard-Reunion-Mobile-Web
REPO_DIR=$DEPLOY_DIR/$REPO_NAME

mkdir -p $DEPLOY_DIR

if [ -d $DEPLOY_DIR/$REPO_NAME ]; then
  cd $REPO_DIR
  git pull
else
  cd $DEPLOY_DIR
  git clone git@github.com:modolabs/$REPO_NAME.git
  cp $REPO_DIR/config/site-config-default.ini $REPO_DIR/config/site-config.ini
  cp $REPO_DIR/config/site-strings-default.ini $REPO_DIR/config/site-strings.ini
fi

sed -e 's!Universitas!Harvard-Reunion!' $REPO_DIR/config/kurogo-default.ini > $REPO_DIR/config/kurogo.ini

mkdir -p $REPO_DIR/site/Harvard-Reunion/cache
cd $REPO_DIR/site/Harvard-Reunion/cache
rm -rf ./minify ./smarty ./Harris ./Calendar
cp -r $REPO_DIR/site/Harvard-Reunion/copy-to-Cache/* $REPO_DIR/site/Harvard-Reunion/cache
cp /opt/haareunions/__private/site-local.ini $REPO_DIR/site/Harvard-Reunion/config
chown -R apache $REPO_DIR
chgrp -R apache $REPO_DIR

if [ ! -L /var/www/html/$SUB_DIR ]; then
  ln -s $REPO_DIR/www /var/www/html/$SUB_DIR
fi

