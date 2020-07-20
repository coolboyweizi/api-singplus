#/bin/env bash
source $HOME/.bashrc

SOURCE_PATH=$PWD

source ${SOURCE_PATH}/continuous_delivery/fetch_configuration.sh

CONF_REPO=ssh://git@git.rd.eaglemobi.cc:33520/sing-plus/conf-api.git
CONF_PATH=$(dirname ${SOURCE_PATH})/configuration
fetch_configuration ${CONF_REPO} ${CONF_PATH}
cp ${CONF_PATH}/build/.env ${SOURCE_PATH}/.env

echo "run database migration"
php ${SOURCE_PATH}/artisan migrate:refresh

echo "run tests ..."
${SOURCE_PATH}/vendor/bin/phpunit ${SOURCE_PATH}/tests/Feature

if [ $? -ne 0 ]; then
  echo "error: phpunit test failed"
  exit 1
fi

echo "create artifacts ..."
CMD="git checkout-index -a -f --prefix=${SOURCE_PATH}/artifacts/"
echo $CMD
`$CMD`

if [ $? -ne 0 ]; then
  echo 'error: create artifacts failed'
  exit 1
fi

cp -rf ${SOURCE_PATH}/vendor ${SOURCE_PATH}/artifacts/

echo "record source revision"
mkdir -p ${SOURCE_PATH}/artifacts/deploy_metas && \
CONF_REVISION=$(git rev-parse HEAD) && \
echo ${CONF_REVISION} > ${SOURCE_PATH}/artifacts/deploy_metas/source_revision

if [ $? -ne 0 ]; then
  echo "error: record source revision failed"
  exit 1
fi

echo "artifacts created."

cd ${SOURCE_PATH}
tar czf artifacts.tar.gz artifacts

if [ $? -ne 0 ]; then
  echo "error: create artifacts tar ball failed"
  exit 1
fi

echo "create artifacts tar ball success"
