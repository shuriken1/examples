<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\File;
use App\Folder;
use App\StoragePermission;
use App\User;
use App\Face;

class FilesController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $files = File::where('user_id', 1)->get();
        $folders = Folder::all();
        return view('files.index', compact('files', 'folders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //return view('files.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $id = base64_encode($id); // DEBUG Temp for testing
        $fileID = base64_decode($id);
        $siteID = 1; // DEBUG Set for testing

        $file = File::find($fileID);
        
        if(!isset($file)) {
            abort(404);
        }

        if($file->canView() == false) {
            abort(403, 'Blocked');
        }

        //$contents = Storage::disk('s3')->get('main/'.$siteID.'/'.$file->filename.'.jpg');
        $contents = Storage::disk('files')->get('original/'.$file->filename.'.jpg');
        
        header("Content-type: $file->contentType");
        echo $contents;
        /*$s3 = \App::make('aws')->createClient('s3');
        try {
            // Get the object.
            $result = $s3->getObject([
                'Bucket' => 'tessera-storage',
                'Key'    => $siteID."/".$file->filename.".jpg"
            ]);
        
            // Display the object in the browser.
            header("Content-Type: {$result['ContentType']}");
            echo $result['Body'];
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }*/

        // exit;

        // $url = "https://<INFORMATION REMOVED FOR GITHUB>/main1/".$siteID."/".$file->filename.".jpg"; // DEBUG Other filetypes? Change auth header below too.

        // $accesskey = "<INFORMATION REMOVED FOR GITHUB>";
        // $currentDate = gmdate("D, d M Y H:i:s T", time());

        // // construct input value
        // $inputValue = "GET\n" . /*VERB*/
        // "\n" . /*Content-Encoding*/
        // "\n" . /*Content-Language*/
        // "\n" . /*Content-Length*/
        // "\n" . /*Content-MD5*/
        // "\n" . /*Content-Type*/
        // "\n" . /*Date*/
        // "\n" . /*If-Modified-Since*/
        // "\n" . /*If-Match*/
        // "\n" . /*If-None-Match*/
        // "\n" . /*If-Unmodified-Since*/
        // "\n" . /*Range*/
        // "x-ms-date:".$currentDate."\n".
        // "x-ms-version:2015-12-11\n" .
        // "/schoolphotos/main1/".$siteID."/".$file->filename.".jpg";

        // //echo $inputValue;
        // //exit;

        // // create base64 encoded signature
        // $hash = hash_hmac('sha256',
        // $inputValue,
        // base64_decode($accesskey),
        // true);
        // $sig = base64_encode($hash);

        // $sig = base64_encode(hash_hmac('sha256', urldecode(utf8_encode($inputValue)), base64_decode($accesskey), true));  
            
                
        // // show result
        // //echo $sig;
        // $headers = [
        //         'Authorization: SharedKey schoolphotos:' . $sig,
        //         'x-ms-date: ' . $currentDate,
        //         'x-ms-version: 2015-12-11'
        //     ];
                
        // // create a new cURL resource
        // $ch = curl_init();

        // // set URL and other appropriate options
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_HEADER, 0);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // // grab URL and pass it to the browser
        // $result = curl_exec($ch);

        // // close cURL resource, and free up system resources
        // curl_close($ch);


        // $filename = basename($url);
        // $file_extension = strtolower(substr(strrchr($filename,"."),1));

        // switch( $file_extension ) {
        //     case "gif": $ctype="image/gif"; break;
        //     case "png": $ctype="image/png"; break;
        //     case "jpeg":
        //     case "jpg": $ctype="image/jpeg"; break;
        //     default:
        // }
        
        // header('Content-type: ' . $ctype);
        // echo $result;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function showUsagePermissions($id) {
        $file = File::find($id);

        if(count($file) == 0) {
            abort(404);
        }

        $perms = $file->usagePermissions()->get();
        foreach($perms as $perm) {
            echo $perm->name." is set to ".$perm->pivot->state."<br>\n";
        }
    }

    public function showStoragePermissions($id) {

        $file = File::find($id);
        $perms = $file->getStoragePermissions();
        
        /*$perms->each(function($perm, $key) {
            $target = $perm->target;
            if($target instanceOf \App\File) {
                echo "Target File ".$target->id."<br>\n";
            } elseif($target instanceOf \App\Folder) {
                echo "Target Folder ".$target->id."<br>\n";
            } elseif($target instanceOf \App\User) {
                echo "Target User ".$target->id."<br>\n";
            }
            
            $permissible = $perm->permissible;
            if($permissible instanceOf \App\User) {
                echo "Permissible User ".$target->id."<br>\n";
            } elseif($permissible instanceOf \App\Role) {
                echo "Permissible Role ".$target->id."<br>\n";
            }
        });*/

        $permList = array('view', 'edit', 'delete',
        'child_folder_view', 'child_folder_edit', 'child_folder_delete', 'child_folder_new',
        'child_file_view', 'child_file_edit', 'child_file_delete', 'child_file_new');

        return view('perms.storage', compact('perms', 'permList', 'file'));
    }

    public function detectFaces($id) {

        $file = File::find($id);
        if(!isset($file)) {
            abort(404);
        }

        // DEBUG Permission check to make sure user is allowed to scan

        $command = 'python '.storage_path('python/face_detect.py')." ".$file->filename;
        exec($command, $output, $status);
        echo"<pre>"; print_r($output); echo"</pre>";
        echo"<br>\nStatus: ".$status."<br><br>\n"; exit;

        if($status != 0) {
            echo"Script error"; // DEBUG make nice.
            exit;
        }

        $faces = json_decode($output[0]);
        //echo"<pre>"; print_r($faces); echo"</pre>";

        foreach($faces as $face) {
            
            // DEBUG Query below might need some fuzziness depending on accuracy of OpenCV.
            $existingFace = Face::where('x', $face[0])->where('y', $face[1])->where('w', $face[2])->where('h', $face[3])->first();
            if(!isset($existingFace)) {
                $newFace = new Face;
                $newFace->file_id = $file->id;
                $newFace->filename = md5($face[0].$face[1].$face[2].$face[3].time());
                $newFace->x = $face[0];
                $newFace->y = $face[1];
                $newFace->w = $face[2];
                $newFace->h = $face[3];
                
                $newFace->save();
            } else {
                echo"Face found in DB.<br>\n";
            }
        }
    }

    public function exif($id) {
        $id = base64_encode($id); // DEBUG Temp for testing
        $fileID = base64_decode($id);
        $siteID = 1; // DEBUG Set for testing

        $file = File::find($fileID);
        
        if(!isset($file)) {
            abort(404);
        }

        if($file->canView() == false) {
            abort(403, 'Blocked');
        }

        $exif = exif_read_data(storage_path('files/original/'.$file->filename.'.jpg'), 'IFD0', true);
        echo"<pre>"; print_r($exif); echo"</pre>";
    }

    public function generateHash($id) {
        $id = base64_encode($id); // DEBUG Temp for testing
        $fileID = base64_decode($id);
        $siteID = 1; // DEBUG Set for testing

        $file = File::find($fileID);
        
        if(!isset($file)) {
            abort(404);
        }

        if($file->canView() == false) {
            abort(403, 'Blocked');
        }

        echo"<pre>"; print(sha1_file(storage_path('files/original/'.$file->filename.'.jpg'))); echo"</pre>";
    }
}
