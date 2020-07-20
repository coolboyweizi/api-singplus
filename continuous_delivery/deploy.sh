#!/bin/env bash

# deploy build
#
# Usage:
#   sh deploy.sh <env> <proj_name>
#
#
# Deploy direcotry structure as below:
# note: diretory mod shoule be: 755, normal file mod should be 644
# deploy_root   # should be /data/go_projects/<proj_name>                                                               
#        *                                                                  
#        *     # project source dir, web root should                        
#        *     # be soft linked to this directory                           
#        ***** current/                                                     
#        *         *                                                        
#        *         ***** # meta info which generated at deploy time         
#        *         *     deloy_metas                                        
#        *         *             *      # record project source revision    
#        *         *             ****** source_revision                     
#        *         *             *                                          
#        *         *             ****** conf_revision                       
#        *         *                                                        
#        *         *                                                        
#        *         ***** .env  ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┊                   
#        *                                              ┊                   
#        *                                              ┊                   
#        *                                              ┊                   
#        *                                              ┊symbolic link      
#        *     # old source backup                      ┊                   
#        ***** 20170224122345.tar.gz                    ┊                   
#        *                                              ┊                   
#        *     # configuration                          ┊                   
#        ***** configuration/                           ┊                   
#        *             *       # env name               ┊                   
#        *             ******* staging/                 ┊                   
#        *             *           *                    ┊                   
#        *             *           ****  .env  ◀┄┄┄┄┄┄┄┄┄                   
#        *             *                                                    
#        *             ******* prod/                                        
#        *             *          *                                         
#        *                        *****  .env                               
#        *                                                                  
#        *                                                                  
#        *     # deploy log, include deploy time, env                       
#        *     # source reversion and conf reversion                        
#        ***** deploy.log                                                   
#        *                                                                  
#        *                                                                  
#        *     # temporary directory, which will be removed                 
#        *     # after deploy finished                                      
#        ***** new_package                                                  
#                                                                  
#                                                                          

set -e
set -x

source $HOME/.bashrc

if [ $# -ne 2 ]; then
  echo "Usage: $0 <env> <proj_name>"
  exit 1
fi

ENV=$1

DEPLOY_ROOT=/data/go_projects/${2}
RUNTIME_ROOT=/data/go_projects_runtime/${2}
RUNTIME_ITEMS="storage"

SOURCE_TAR_BALL=$PWD/build_package/artifacts.tar.gz
BUILD_PATH=$PWD/build_package/artifacts
CONF_REPO="ssh://git@git.rd.eaglemobi.cc:33520/sing-plus/conf-api.git"
CONF_PATH=${DEPLOY_ROOT}/configuration
NEW_PACKAGE=${DEPLOY_ROOT}/new_package
DEPLOY_LOG=${DEPLOY_ROOT}/deploy.log


function copy_package ()
{
  #cd $PWD/build_package
  #tar xzf ${SOURCE_TAR_BALL}
  echo "cp ${BUILD_PATH} to ${NEW_PACKAGE}"

  mkdir -p ${DEPLOY_ROOT}
  rm -rf ${NEW_PACKAGE}
  cp -rf ${BUILD_PATH} ${NEW_PACKAGE}
  if [ ! -f ${NEW_PACKAGE}/deploy_metas/source_revision ]; then
    echo "error: deploy_metas/source_revision: file not exists"
    exit 1
  fi
  SOURCE_REVISION=$(cat ${NEW_PACKAGE}/deploy_metas/source_revision)
  echo "copy done, source: ${SOURCE_REVISION} deploy starting ..."
}

function deploy_configuration ()
{
  echo "update configuration from repo"

  if [ ! -d ${CONF_PATH} ]; then
    git clone ${CONF_REPO} ${CONF_PATH}
    cd ${CONF_PATH}
  else
    cd ${CONF_PATH}
    git pull
  fi

  CONF_REVISION=$(git rev-parse HEAD)
  echo ${CONF_REVISION} > ${NEW_PACKAGE}/deploy_metas/conf_revision

  if [ ! -f ${CONF_PATH}/${ENV}/.env ]; then
    echo "error: ${ENV} configuration is not exists"
    exit 1
  fi
  cp ${CONF_PATH}/${ENV}/.env ${NEW_PACKAGE}/.env
  echo "deploy configuration done"
}

function create_runtime_symbolic_link ()
{
  echo "create runtime symbolic link"
  for ITEM in $RUNTIME_ITEMS; do
    echo "create runtime symbolic link to ${RUNTIME_ROOT}/$ITEM"

    if [ ! -e ${RUNTIME_ROOT}/$ITEM ]; then
      echo "error: runtime file not exists"
      exit 1
    fi

    rm -rf ${NEW_PACKAGE}/${ITEM}
    ln -s ${RUNTIME_ROOT}/$ITEM ${NEW_PACKAGE}/${ITEM}
  done
  echo "create runtime symbolic link done"
}

function deploy ()
{
  echo "make tar ball for old version for backup. deploy new version"
  DEPLOY_TIME=$(date +%Y%m%d%H%I%S)
  if [ -d ${DEPLOY_ROOT}/current ]; then
    tar czfP ${DEPLOY_ROOT}/${DEPLOY_TIME}.tar.gz ${DEPLOY_ROOT}/current
    rm -rf ${DEPLOY_ROOT}/current
  fi
  mv ${NEW_PACKAGE} ${DEPLOY_ROOT}/current

  cd ${DEPLOY_ROOT}/current
  php artisan optimize
  php artisan config:cache
  php artisan view:clear
  php artisan migrate
  php artisan queue:restart

  echo -e "[${DEPLOY_TIME}] -- env: ${ENV};  revision: ${SOURCE_REVISION};"\
        "conf_revision: ${CONF_REVISION} \n" >> ${DEPLOY_LOG}

  rm -rf ${NEW_PACKAGE}

  echo "deploy done"
}

copy_package
deploy_configuration
create_runtime_symbolic_link
deploy
