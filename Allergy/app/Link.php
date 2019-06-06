<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Link extends Model
{
    protected $fillable = [
        'code',
        'expires_at'
    ];

    function delete() {
        if(Storage::disk('qr')->exists($this->id)) {
            Storage::disk('qr')->delete($this->id);
        }
        
        parent::delete();
    }

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function linkable() {
        return $this->morphTo();
    }

    public function generateQR() {
        Storage::disk('qr')->put($this->id, QrCode::format('png')->size(360)->margin('0')->generate("http://allergenie.tier1digital.co.uk/l/".$this->code));
    }

    public function getIsActiveAttribute() {
        if(is_null($this->expires_at) OR ($this->expires_at > date("Y-m-d H:i:s"))) {
            return true;
        } else {
            return false;
        }
    }

    public function isEnabled() {
        return $this->enabled;
    }
}
