#/bin/env bash

# gen_h5 $H5_PATH $ARTIFACTS
function add_h5 ()
{
  echo "Add h5 ..."
  H5_PATH=$1
  ARTIFACTS=$2

  cd $H5_PATH
  git checkout-index -a -f --prefix=$ARTIFACTS/public/h5/

  if [ -d ${ARTIFACTS}/public/c ]; then
    mv ${ARTIFACTS}/public/c ${ARTIFACTS}/public/c_back
  fi

  mv ${ARTIFACTS}/public/h5/c ${ARTIFACTS}/public/
}
