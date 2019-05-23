#!/usr/bin/env python

import sys, errno
import numpy as np
import cv2 as cv
from PIL import Image
import piexif
import hashlib

file_root = '/var/www/html/storage/files/original/'
file_name = sys.argv[1] if len(sys.argv) > 1 else None

if file_name == None:
  print('No file name')
  sys.exit()

file_path = file_root + file_name + '.jpg'

#openedFile = open(file_path)
#readFile = openedFile.read()

#sha1Hash = hashlib.sha1(readFile)
#sha1Hashed = sha1Hash.hexdigest()

img = cv.imread(file_path)
pil_img = Image.open(file_path)

# load exif data
try:
  exif_dict = piexif.load(pil_img.info["exif"])
  exif_dict.pop("thumbnail")
  exif_dict.pop("1st")
except:
  #print('No exif data')
  #sys.exit()

  zeroth_ifd = {}
  exif_ifd = {}
  gps_ifd = {}
  exif_dict = {"0th":zeroth_ifd, "Exif":exif_ifd, "GPS":gps_ifd}

pil_img.close()

# for ifd_name in exif_dict:
#   print("\n{0} IFD:".format(ifd_name))
#   for key in exif_dict[ifd_name]:
#     try:
#       print(piexif.TAGS[ifd_name][key]["name"], exif_dict[ifd_name][key][:10])
#     except:
#       print(piexif.TAGS[ifd_name][key]["name"], exif_dict[ifd_name][key])
# sys.exit()

# Set exif orientation to normal
exif_dict['0th'][piexif.ImageIFD.Orientation] = 1

# Set exif sizes to match image
exif_dict['Exif'][piexif.ExifIFD.PixelXDimension] = img.shape[1]
exif_dict['Exif'][piexif.ExifIFD.PixelYDimension] = img.shape[0]

# Swap exif resolutions in case they're relevant
#exif_dict['0th'][piexif.ImageIFD.XResolution] = (img.shape[1], 1)
#exif_dict['0th'][piexif.ImageIFD.YResolution] = (img.shape[0], 1)

# Update exif bytes
exif_bytes = piexif.dump(exif_dict)

if img is None:
  print('File could not be opened')
  print(file_root + file_name + '.jpg')
  sys.exit()

cv.imwrite(file_path, img)
piexif.insert(exif_bytes, file_path)

#print('Done')