#!/bin/sh

rm -f salesman.ocmod.zip
cp -R src upload
zip -rm salesman.ocmod.zip upload
