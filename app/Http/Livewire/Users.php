<?php

namespace App\Http\Livewire;

use DB;
use App\Http\Requests\UserRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Image;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Users extends Component
{
    use WithFileUploads, WithPagination;

    public $first_name, $last_name, $image, $imageEdit, $documents, $document_type_id, $document_number;
    public $countries, $country_id, $states, $state_id, $cities, $city_id, $address;
    public $phone_codes, $phone_code_id, $phone, $roles, $role_id, $selectedRoles = [];
    public $status, $email, $password, $password_confirmation, $user_id;
    public $addUser = false, $updateUser = false, $deleteDetailModal = false, $deleteUser = false;

    #[Title('Users')]
    public function rules()
    {
        return UserRequest::rules($this->user_id);
    }

    public function resetFields()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->image = '';
        $this->imageEdit = '';
        $this->documents = [];
        $this->document_type_id = '';
        $this->document_number = '';
        $this->countries = [];
        $this->country_id = '';
        $this->states = [];
        $this->state_id = '';
        $this->cities = [];
        $this->city_id = '';
        $this->address = '';
        $this->phone_codes = [];
        $this->phone_code_id = '';
        $this->roles = [];
        $this->role_id;
        $this->selectedRoles = [];
        $this->phone = '';
        $this->status = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function resetValidationAndFields()
    {
        $this->resetValidation();
        $this->resetFields();
        $this->addUser = false;
        $this->updateUser = false;
        $this->deleteDetailModal = false;
        $this->deleteUser = false;
    }

    public function mount()
    {
        if (Gate::denies('user_index')) {
            return redirect()->route('dashboard')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }
    }

    public function render()
    {
        $users = User::orderBy('first_name', 'asc')->paginate(10);
        return view('user.index', compact('users'));
    }

    public function create()
    {
        if (Gate::denies('user_add')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }
        $this->resetValidationAndFields();
        $this->user_id     = '';
        $this->phone_codes = Country::orderBy('name', 'asc')->get();
        $this->countries   = $this->phone_codes;
        $this->documents   = DocumentType::where('status', 1)->orderBy('name', 'asc')->get();
        $this->roles       = Role::where('status', 1)->orderBy('title', 'asc')->get();
        $this->addUser     = true;
        return view('user.create');
    }

    public function countryChange($country_id)
    {
        $this->states = [];
        $this->state_id = '';
        $this->cities = [];
        $this->city_id = '';
        if ($country_id != '') {
            $this->states = State::where('country_id', $country_id)->get();
        }
    }

    public function stateChange($state_id)
    {
        $this->cities = [];
        $this->city_id = '';
        if ($state_id != '') {
            $this->cities = City::where('state_id', $state_id)->get();
        }
    }

    public function store()
    {
        if (Gate::denies('user_add')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }
        $this->validate();

        DB::beginTransaction();
        $user = User::create([
            'first_name'       => Str::title($this->first_name),
            'last_name'        => Str::title($this->last_name),
            'document_type_id' => $this->document_type_id,
            'document_number'  => $this->document_number,
            'city_id'          => $this->city_id,
            'address'          => $this->address,
            'phone_code_id'    => $this->phone_code_id,
            'phone'            => $this->phone,
            'email'            => Str::lower($this->email),
            'password'         => Hash::make($this->password),
        ]);

        $user->roles()->attach($this->selectedRoles);
        $user->save();
        DB::commit();

        DB::beginTransaction();
        if ($this->image != '') {
            if ($user->image) {
                if (Storage::exists('public/images/'.$user->image->url)) {
                    Storage::delete('public/images/'.$user->image->url);
                }
                $user->image->delete();
            }
            $file = $this->image->storePublicly('public/images');
            $user->image()->create([
                'url' => substr($file, strlen('public/images/')),
            ]);
        }
        DB::commit();

        return redirect()->route('users')
            ->with('message', trans('message.Created Successfully.', ['name' => __('User')]))
            ->with('alert_class', 'success');
    }

    public function edit($id)
    {
        if (Gate::denies('user_edit')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }

        $user = User::find($id);

        if (!$user) {
            return redirect()->route('users')
                ->with('message', __('User not found'))
                ->with('alert_class', 'danger');
        }
        $this->resetValidationAndFields();
        $this->user_id          = $user->id;
        $this->first_name       = $user->first_name;
        $this->last_name        = $user->last_name;
        $this->image            = $user->image->url ?? '';
        $this->imageEdit        = $user->image->url ?? '';
        $this->documents        = DocumentType::orderBy('name', 'asc')->get();
        $this->document_type_id = $user->document_type_id;
        $this->document_number  = $user->document_number;
        $this->city_id          = $user->city_id;
        $this->cities           = City::where('state_id', $user->city->state_id)->orderBy('name', 'asc')->get();
        $this->state_id         = $user->city->state_id;
        $this->states           = State::where('country_id', $user->city->state->country_id)->orderBy('name', 'asc')->get();
        $this->country_id       = $user->city->state->country_id;
        $this->countries        = Country::orderBy('name', 'asc')->get();
        $this->address          = $user->address;
        $this->phone_codes      = $this->countries;
        $this->phone_code_id    = $user->phone_code_id;
        $this->phone            = $user->phone;
        $this->status           = $user->status;
        $this->email            = $user->email;
        $this->roles            = Role::orderBy('title', 'asc')->get();
        $this->selectedRoles    = $user->roles;
        $this->updateUser       = true;
        $this->updateRoleList();
        return view('user.edit');
    }

    public function update()
    {
        if (Gate::denies('user_edit')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }

        $this->validate();

        $user = User::find($this->user_id);
        if (!$user) {
            return redirect()->route('users')
                ->with('message', __('User not found'))
                ->with('alert_class', 'danger');
        }

        if (gettype($this->image) != 'string' && $this->image != '') {
            $file = $this->image->storePublicly('public/images');
            $this->image = substr($file, strlen('public/images/'));
            if (isset($user->image) && Storage::exists('public/images/'.$user->image->url)) {
                Storage::delete('public/images/'.$user->image->url);
            }
        } else {
            $this->image = $user->image->url;
        }

        DB::beginTransaction();
        $user->first_name       = Str::title($this->first_name);
        $user->last_name        = Str::title($this->last_name);
        $user->document_type_id = $this->document_type_id;
        $user->document_number  = $this->document_number;
        $user->city_id          = $this->city_id;
        $user->address          = $this->address;
        $user->phone_code_id    = $this->phone_code_id;
        $user->phone            = $this->phone;
        $user->status           = $this->status;
        $user->email            = Str::lower($this->email);
        $user->image->url       = $this->image;
        $user->image->save();

        if (isset($this->password) || $this->password != '') {
            $user->password = Hash::make($this->password);
        }
        $user->roles()->detach();
        $user->roles()->attach($this->selectedRoles);
        $user->save();
        DB::commit();

        return redirect()->route('users')
            ->with('message', trans('message.Updated Successfully.', ['name' => __('User')]))
            ->with('alert_class', 'success');
    }

    public function updateRoleList()
    {
        try {
            $roles = Role::where('status', 1)->orderBy('title', 'asc')->get();
            $this->roles = $roles->diff($this->selectedRoles);
        } catch (\Throwable $th) {
            $message = throwableException($th);
            return redirect()->route('users')
                ->with('message', $message)
                ->with('alert_class', 'danger');
        }
    }

    public function addDetail()
    {
        $this->validate([
            'role_id' => 'required',
        ], [
            'role_id.required' => trans('validation.required', ['attribute' => __('Role')]),
        ]);

        if ($this->role_id) {
            try {
                $role = Role::find($this->role_id);
                $this->selectedRoles[] = $role;

                // Remueve el curso seleccionado de la lista de cursos disponibles
                foreach ($this->roles as $key => $value) {
                    if ($value->id === $this->role_id) {
                        unset($this->roles[$key]);
                        break;
                    }
                }

                $this->role_id = '';
                $this->updateRoleList();
            } catch (\Throwable $th) {
                $message = throwableException($th);
                return redirect()->route('users')
                    ->with('message', $message)
                    ->with('alert_class', 'danger');
            }
        }  else {
            $this->addError('role_id', trans('validation.required', ['attribute' => __('Role')]));
        }
    }

    public function setCleanId($role_id)
    {
        try {
            // Eliminar el registro seleccionado del array
            foreach ($this->selectedRoles as $key => $value) {
                if ($value->id === $role_id) {
                    unset($this->selectedRoles[$key]);
                    break;
                }
            }
            $this->updateRoleList();
        } catch (\Throwable $th) {
            $message = throwableException($th);
            return redirect()->route('users')
                ->with('message', $message)
                ->with('alert_class', 'danger');
        }
    }

    public function cancelDetail()
    {
        $this->deleteDetailModal = false;
    }

    public function setDeleteDetailId($role_id)
    {
        if (Gate::denies('user_edit')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }

        $this->role_id = $role_id;
        $this->deleteDetailModal = true;
    }

    public function deleteDetail()
    {
        if (Gate::denies('user_edit')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }
        try {
            $user = User::find($this->user_id);
            if (!$user) {
                return redirect()->route('users')
                    ->with('message', __('User not found'))
                    ->with('alert_class', 'danger');
            }
            DB::beginTransaction();
            $user->roles()->detach($this->role_id);
            DB::commit();

            // Eliminar el registro seleccionado del array
            foreach ($this->selectedRoles as $key => $value) {
                if ($value->id === $this->role_id) {
                    unset($this->selectedRoles[$key]);
                    break;
                }
            }
            $this->updateRoleList();
            $this->dispatch('scrollTop');
            $this->deleteDetailModal = false;

            session()->flash('message_detail', trans('message.Deleted Successfully.', ['name' => __('Role')]));
            session()->flash('alert_class', 'success_detail');
        } catch (\Throwable $th) {
            $message = throwableException($th);
            return redirect()->route('users')
                ->with('message', $message)
                ->with('alert_class', 'danger');
        }
    }

    public function cancel()
    {
        $this->resetValidationAndFields();
    }

    public function setDeleteId($id)
    {
        if (Gate::denies('user_delete')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }

        $user = User::find($id);
        if (!$user) {
            return redirect()->route('users')
                ->with('message', __('User not found'))
                ->with('alert_class', 'danger');
        }
        $this->user_id = $user->id;
        $this->resetValidationAndFields();
        $this->deleteUser = true;
    }

    public function delete()
    {
        if (Gate::denies('user_delete')) {
            return redirect()->route('users')
                ->with('message', trans('message.You do not have the necessary permissions to execute the action.'))
                ->with('alert_class', 'danger');
        }

        $user = User::find($this->user_id);
        if (!$user) {
            return redirect()->route('users')
                ->with('message', __('User not found'))
                ->with('alert_class', 'danger');
        }
        if (isset($user->image) && Storage::exists('public/images/'.$user->image->url)) {
            Storage::delete('public/images/'.$user->image->url);
        }

        DB::beginTransaction();
        $user->roles()->detach();
        $user->image()->delete();
        $user->delete();
        DB::commit();

        return redirect()->route('users')
            ->with('message', trans('message.Deleted Successfully.', ['name' => __('User')]))
            ->with('alert_class', 'success');
    }
}