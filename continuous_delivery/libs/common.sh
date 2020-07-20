#/bin/env bash

# prepare_artifacts $SOURCE_PATH $ARTIFACTS
function prepare_artifacts ()
{
  echo "Extract clean source from git work space ..."
  SOURCE_PATH=$1
  ARTIFACTS=$2

  cd $SOURCE_PATH
  rm -rf $ARTIFACTS
  git checkout-index -a -f --prefix=$ARTIFACTS/

  mkdir $ARTIFACTS/deploy_metas
  echo $(git rev-parse HEAD) > $ARTIFACTS/deploy_metas/source_revision
}

# add_configuration $CONF_REPO $CONF_PATH $CONF_ENV $ARTIFACTS
function add_configuration ()
{
  echo "Add conf ..."
  CONF_REPO=$1
  CONF_PATH=$2
  CONF_ENV=$3
  ARTIFACTS=$4

  if [ ! -d ${CONF_PATH} ]; then
    git clone $CONF_REPO $CONF_PATH
  else
    cd $CONF_PATH
    git pull
  fi

  cp ${CONF_PATH}/${CONF_ENV}/.env ${ARTIFACTS}/.env
}

# gen_vendor $BACK_PATH $ARTIFACTS
function add_vendor ()
{
  echo "Add vendor ..."
  BACK_PATH=$1
  ARTIFACTS=$2

  cd $ARTIFACTS
  if [ ! -e ${BACK_PATH} ]; then
    mkdir -p ${BACK_PATH}
  fi

  if [ ! -e ${BACK_PATH}/vendor ]; then
    echo "Back vendor not exists, so we execute composer install in ${ARTIFACTS}"
    composer install
    cp -rf ${ARTIFACTS}/vendor ${BACK_PATH}/
    cp -rf ${ARTIFACTS}/composer.lock ${BACK_PATH}/composer.lock
  else
    echo "Copy back vendor into $ARTIFACTS"

    cp -rf ${BACK_PATH}/vendor ${ARTIFACTS}/
    if ! is_file_not_changed ${ARTIFACTS}/composer.lock ${BACK_PATH}/composer.lock ; then
      echo "composer.lock changed, so execute composer install to update vendor"
      composer install
      cp -rf ${ARTIFACTS}/vendor ${BACK_PATH}/
      cp -rf ${ARTIFACTS}/composer.lock ${BACK_PATH}/composer.lock
    fi
  fi

  composer dump-autoload
}

# run_test $ARTIFACTS
function run_test ()
{
  echo "Run unit test ..."
  ARTIFACTS=$1

  cd $ARTIFACTS
  php artisan migrate:refresh
  vendor/bin/phpunit tests/Feature
  rm .env
}

# is_file_not_changed $file1 $file2
function is_file_not_changed ()
{
  F1_CHECK_SUM=$(md5sum $1 | awk '{print $1}')
  F2_CHECK_SUM=$(md5sum $2 | awk '{print $1}')

  if [ ${F1_CHECK_SUM} = ${F2_CHECK_SUM} ]; then
    return 0
  else
    return 1
  fi
}
