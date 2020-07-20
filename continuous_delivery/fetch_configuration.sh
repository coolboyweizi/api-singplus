#!/bin/env bash
source $HOME/.bashrc

function fetch_configuration ()
{
  CURRENT_DIR=$PWD
  if [ $# -ne 2 ]; then
    echo "Usage: argument: <conf_repo> <target_dir>"
    exit 1
  fi

  CONF_REPO=$1
  CONF_PATH=$2
  echo "fetch configuration from repo"

  if [ ! -d ${CONF_PATH} ]; then
    git clone ${CONF_REPO} ${CONF_PATH} && cd ${CONF_PATH}
  else
    cd ${CONF_PATH} && git pull
  fi

  if [ $? -ne 0 ]; then
    echo "error: fetch configuration failed"
    exit 1
  fi

  cd $CURRENT_DIR;
}
