#!/usr/bin/env python

import sys, errno
import numpy as np
import cv2 as cv
import json

file_root = '/var/www/html/storage/files/'
#print(sys.argv[1])
file_name = sys.argv[1] if len(sys.argv) > 1 else None

if file_name == None:
  print('No file name')
  sys.exit()

file_path = file_root + file_name + '.jpg'
#print(file_path)
img = cv.imread(file_root + file_name + '.jpg')

if img is None:
  print(os.strerror(os.errno.ENOENT))
  sys.exit(errno.ENOENT)

gray = cv.cvtColor(img, cv.COLOR_BGR2GRAY)

face_cascade = cv.CascadeClassifier('/var/www/html/storage/python/face.xml')
#eye_cascade = cv.CascadeClassifier('eye.xml')

faces = face_cascade.detectMultiScale(gray, 1.2, 5)

print json.dumps(faces.tolist())

#print("Found {0} faces!".format(len(faces)))
#sys.exit()

#for (x,y,w,h) in faces:
#    cv.rectangle(img,(x,y),(x+w,y+h),(0,255,0),2)
#    roi_gray = gray[y:y+h, x:x+w]
#    roi_colour = img[y:y+h, x:x+w]
#    eyes = eye_cascade.detectMultiScale(roi_gray, 1.3, 5)
#    for (ex,ey,ew,eh) in eyes:
#        cv.rectangle(roi_colour,(ex,ey),(ex+ew,ey+eh),(0,128,255),2)


#cv.imwrite('/var/www/html/images/new4.jpg', img)
#print('done')
