#!/bin/env bash

# build stage script
# this script must be executed in pipeline project root directory

source $HOME/.bashrc

set -e
set -x

PROJECT=$1
WORKDIR=$PWD
BACK_PATH=$(dirname $(dirname $WORKDIR))/pipeline_back/${GO_PIPELINE_NAME}
SOURCE_PATH=$WORKDIR/source
H5_PATH=$WORKDIR/h5
CONF_PATH=$WORKDIR/configuration
CONF_REPO=ssh://git@git.rd.eaglemobi.cc:33520/sing-plus/conf-api.git
CONF_ENV=${PROJECT:+${PROJECT}_}build
ARTIFACTS=$WORKDIR/artifacts

source ${SOURCE_PATH}/continuous_delivery/libs/common.sh
source ${SOURCE_PATH}/continuous_delivery/libs/h5.sh

prepare_artifacts $SOURCE_PATH $ARTIFACTS
add_configuration $CONF_REPO $CONF_PATH $CONF_ENV $ARTIFACTS
add_vendor $BACK_PATH $ARTIFACTS
add_h5 $H5_PATH $ARTIFACTS
run_test $ARTIFACTS
