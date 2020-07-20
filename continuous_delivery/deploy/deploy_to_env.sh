#!/bin/env bash

# deploy build
#
# Usage:
#   sh deploy_to_env.sh <tarball_path> <proj_name> [group]
#       group:  available value: primary
#
#
# Deploy direcotry structure as below:
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
#        *         ***** .env  ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄      
#        *                                                    
#        *                                                    
#        *                                                    
#        *                                                    
#        *     # old source backup                            
#        ***** 20170224122345.tar.gz                          
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

set -e
set -x

if [ $# -ne 2 -a $# -ne 3 ]; then
  echo "Usage: $0 <project> <tarball_path> [primary]"
  exit
fi

PROJECT=$1
TARBALL=$2
GROUP=$3

DEPLOY_TIME=$(date +%Y%m%d%H%I%S)

WWW_ENTRY_ROOT=/data/www
DEPLOY_ROOT=/data/go_projects/$PROJECT
RUNTIME_ROOT=/data/go_projects_runtime/$PROJECT
NEW_SOURCE=$DEPLOY_ROOT/${DEPLOY_TIME}

function extract_source ()
{
  mkdir -p ${NEW_SOURCE}
  tar -xzf $TARBALL -C $NEW_SOURCE
}

function create_runtime_symbolic_link ()
{
  echo "create runtime symbolic link"

  if [ ! -e ${RUNTIME_ROOT}/storage ]; then
    mkdir -p ${RUNTIME_ROOT}/storage
    cp -rf ${NEW_SOURCE}/storage/* ${RUNTIME_ROOT}/storage
  fi

  rm -rf ${NEW_SOURCE}/storage
  ln -s ${RUNTIME_ROOT}/storage ${NEW_SOURCE}/storage
}

function deploy ()
{
  echo "make tar ball for old version for backup. deploy new version"

  SOURCE_LINK=${DEPLOY_ROOT}/current
  OLD_SOURCE=NULL
  if [ -L ${SOURCE_LINK} ]; then
    if [ -d ${SOURCE_LINK} ]; then
      OLD_SOURCE=$(realpath ${SOURCE_LINK})
      tar czf ${DEPLOY_ROOT}/history_$(basename ${OLD_SOURCE}).tar.gz ${OLD_SOURCE} -C ${DEPLOY_ROOT}
    fi
    rm -rf ${SOURCE_LINK}
  fi

  ln -s ${NEW_SOURCE} ${SOURCE_LINK}

  if [ -d ${OLD_SOURCE} -a "${OLD_SOURCE}" != "${NEW_SOURCE}" ]; then
    rm -rf $OLD_SOURCE
  fi
}

function post_deploy ()
{
  echo "Execute laravel project scripts"
  cd ${DEPLOY_ROOT}/current
  php artisan optimize
  php artisan config:cache
  php artisan view:clear
  # 只有primary机器才执行database migrate task
  if [ "$GROUP" = "primary" ]; then
    php artisan migrate
  fi
  php artisan queue:restart

  SOURCE_REVISION=$(cat ${NEW_SOURCE}/deploy_metas/source_revision)
  echo -e "[${DEPLOY_TIME}] -- revision: ${SOURCE_REVISION};\n" >> ${DEPLOY_ROOT}/deploy.log
}

function clear_history ()
{
  cd $DEPLOY_ROOT
  HISTORY_MAX=10
  DEL_NUM=$(expr $(find . -maxdepth 1 -name "history_*.tar.gz" | wc -l) - ${HISTORY_MAX})

  if [ $DEL_NUM -gt 0 ]; then
    for history_file in $(ls ./history_*.tar.gz | sort | sed -n "1,${DEL_NUM}p"); do
      rm -f $history_file
    done
  fi
}

# gen_www_entry $ENTRY $CURRENT_SOURCE
function gen_www_entry ()
{
  PROJ_ENTRY=$1
  CURRENT_SOURCE=$2

  if [ ! -L $PROJ_ENTRY ]; then
    ln -s $CURRENT_SOURCE $PROJ_ENTRY
  fi
}

if [ "$GROUP" = "primary" ]; then
  echo "Deploy proj to primary env"
fi

extract_source
create_runtime_symbolic_link
deploy
post_deploy
clear_history
gen_www_entry ${WWW_ENTRY_ROOT}/$PROJECT ${DEPLOY_ROOT}/current
