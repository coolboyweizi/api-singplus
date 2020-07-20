#!/bin/env bash

###################################
#   Create env related tar ball
###################################
set -e
set -x

if [ $# -ne 3 ]; then
  echo "Usage: $0 <project> <env> <artifacts_path>"
  exit 1
fi

PROJECT=$1
ENV=$2
ARTIFACTS=$(realpath $3)

WORKDIR=$PWD
CWD="$(cd -P -- "$(dirname -- "$0")" && pwd -P)"
CONF_REPO="ssh://git@git.rd.eaglemobi.cc:33520/sing-plus/conf-api.git"

function check_meta ()
{
  if [ ! -f ${ARTIFACTS}/deploy_metas/source_revision ]; then
    echo "error: source_revision file not exists in artifacts"
    exit 1
  fi
  SOURCE_REVISION=$(cat ${ARTIFACTS}/deploy_metas/source_revision)
}

function configure ()
{
  echo 'Set configuration for env before deployment'

  rm -rf ./tmp_conf
  git clone ${CONF_REPO} ./tmp_conf
  cd ./tmp_conf

  CONF_REVISION=$(git rev-parse HEAD)
  echo ${CONF_REVISION} > ${ARTIFACTS}/deploy_metas/conf_revision

  if [ ! -f ./${ENV}/.env ]; then
    echo "error: ${ENV} configuration is not exists"
    exit 1
  fi
  cp ./${ENV}/.env ${ARTIFACTS}/.env
  cd $WORKDIR

  echo "${ENV} configuration aready set"
}

function gen_tarball ()
{
  tar -czf artifacts.tar.gz -C ${ARTIFACTS} .
  ENV_TARBALL=$(realpath artifacts.tar.gz)
  echo "env tar ball ${ENV_TARBALL} generated"
}

# Dispatch tar ball and deploy scripts to env using saltstack
function proj_dispatch ()
{
  echo "dispatch $PROJECT $ENV $ENV_TARBALL $CWD/deploy_to_env.sh"
  /usr/bin/dispatch $PROJECT $ENV $ENV_TARBALL $CWD/deploy_to_env.sh
}

check_meta
configure
gen_tarball
proj_dispatch $PROJECT $ENV $ENV_TARBALL $CWD/deploy_to_env.sh
