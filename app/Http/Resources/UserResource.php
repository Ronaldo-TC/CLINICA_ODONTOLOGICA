<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'usernumber'      => $this->usernumber,
            'userci'          => $this->userci,
            'username'        => $this->username,
            'userfullname'    => $this->userfullname,
            'userdesc'        => $this->userdesc,
            'usertype'        => $this->usertype,
            'userenabled'     => (bool) $this->userenabled,
            'userpermitlogin' => (bool) $this->userpermitlogin,
            'usermultilogin'  => (bool) $this->usermultilogin,
            'userip'          => $this->userip,
            'userpermitip'    => $this->userpermitip,
            'userinfo'        => $this->userinfo,
            'userlastlogin'   => $this->userlastlogin,
            'userlastlogout'  => $this->userlastlogout,
            'updatetime'      => $this->updatetime,

            // Relaciones opcionales (solo si se cargaron con ->with())
            'specialties' => $this->whenLoaded('specialties', function () {
                return $this->specialties->map(fn($s) => [
                    'clinicalid'              => $s->clinicalid,
                    'clinicalspecialty'       => $s->clinical?->clinicalspecialty,
                    'enabled'                 => (bool) $s->enabled,
                    'rotate'                  => $s->rotate,
                    'responsibleadmission'    => (bool) $s->responsibleadmission,
                ]);
            }),
        ];
    }
}
