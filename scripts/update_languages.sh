#!/bin/bash
#
###############################################################################################
#
# Project:      Language selector related plugin
# Script:       Generate the .mo files for the translations.
#
# Author:       Ruben Vasallo
# Date:         2014-02-05
# Last Modif:   2014-02-05
# Change Log:   2014-02-05 - First Version
#
###############################################################################################
#
#

# ---------------------------------------------------------------------------------------------
#                                         Configurations
# ---------------------------------------------------------------------------------------------
gettextDomain="language-selector-related"
langsPaths="../languages"

# ---------------------------------------------------------------------------------------------
#                                         Main
# ---------------------------------------------------------------------------------------------
if [ $1 ]; then
    gettextDomain=$1
fi

cd `dirname "$0"`

# Generate a js file for each language
for langDir in $(find $langsPaths -maxdepth 1 -type f -exec basename {} \; | egrep '[a-z]{2}_{0,1}[A-Z]{0,2}.pot$' | sed 's/.pot//g')
do

    langtoprocess=$(echo $langDir | sed s/$gettextDomain-//g)

    echo "Generating $gettextDomain-$langtoprocess.mo"

    msgfmt -o $langsPaths/$gettextDomain-$langtoprocess.mo $langsPaths/$langDir

done
