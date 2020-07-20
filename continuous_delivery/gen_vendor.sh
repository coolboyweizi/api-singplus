#/bin/env bash

# generate vendor for laravel project

source $HOME/.bashrc

SOURCE_PATH=$PWD
BACK_PATH=$(dirname $(dirname $(dirname $PWD)))/pipeline_back/${GO_PIPELINE_NAME}

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

echo "start generate composer vendor."

if [ ! -e ${BACK_PATH} ]; then
  mkdir -p ${BACK_PATH}
fi

cd $SOURCE_PATH

if [ ! -e ${BACK_PATH}/vendor ]; then
  echo "back vendor not exists, so we execute composer install in ${SOURCE_PATH}"
  composer install && \
  cp -rf ${SOURCE_PATH}/vendor ${BACK_PATH}/ && \
  cp -rf ${SOURCE_PATH}/composer.lock ${BACK_PATH}/composer.lock
  echo "vendor and back vendor already be generated"

else
  echo "copy back vendor into ${SOURCE_PATH}"

  cp -rf ${BACK_PATH}/vendor ${SOURCE_PATH}/
  if [ $? -ne 0 ]; then
    echo "error: copy failed"
    exit 1
  fi

  if ! is_file_not_changed ${SOURCE_PATH}/composer.lock ${BACK_PATH}/composer.lock ; then
    echo "composer.lock changed, so execute composer install to update vendor"
    composer install && \
    cp -rf ${SOURCE_PATH}/vendor ${BACK_PATH}/ && \
    cp -rf ${SOURCE_PATH}/composer.lock ${BACK_PATH}/composer.lock
    if [ $? -ne 0 ]; then
      echo "error: vendor updated failed"
      exit 1 
    fi
  fi
fi

composer dump-autoload

echo "vendor generation aready success"
