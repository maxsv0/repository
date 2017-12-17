#!/bin/bash

if [ -z "$1" ]
  then
    echo "Please specify module name"
	exit 0
fi


if [ -z "$2" ]
  then
    echo "Please specify repository KEY"
	exit 0
fi


if [ -z "$3" ]
  then
    echo "Please specify Module Title"
	exit 0
fi


if [ -z "$4" ]
  then
    echo "Please specify Module Version"
	exit 0
fi


if [ -z "$5" ]
  then
    echo "Please specify Module Released Date"
	exit 0
fi


if [ -z "$6" ]
  then
    echo "Please specify Module Description"
	exit 0
fi


echo "Publish to  to MSV repository"
echo "========> $1 (key :  $2)"
echo $3
echo $4
echo $5
echo $6
echo "======================="

mkdir src-temp
cp -a src/. src-temp
find src-temp/ -name .DS_Store -delete

echo "Creating archive.zip.."
cd src-temp
zip -r ../archive.zip .
cd ..

echo "#file list">filelist.txt
echo "#module">>filelist.txt
find src-temp/module -type f -regex "^.*$">>filelist.txt
echo "#content">>filelist.txt
find src-temp/content -type f -regex "^.*$">>filelist.txt
echo "#template">>filelist.txt
find src-temp/template -type f -regex "^.*$">>filelist.txt

echo "Sending file to repository.."
curl -F "file=@archive.zip"  -F "filelist=@filelist.txt" -F "module=$1" -F "key=$2" -F "title=$3" -F "version=$4" -F "released=$5" -F "description=$6" http://rep.msvhost.com/api/import/
echo "Done!"

echo "Removing temp files.."
rm -R src-temp
rm archive.zip
rm filelist.txt
echo "Done!"

exit 0