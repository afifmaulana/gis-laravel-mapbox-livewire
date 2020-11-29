<?php

namespace App\Http\Livewire;

use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use Livewire\Component;
use App\Models\Marker;

class MyTest extends Component
{
    use WithFileUploads;
    
    public $count = 5; 
    public $locationId,$long,$lat,$title,$description,$image; 
    public $imageUrl; 
    public $geoJson; 
    public $isEdit = false;

    private function getLocations() {
        $locations = Marker::orderBy('created_at', 'desc')->get();

        $customLocation = [];

        foreach($locations as $location){
            $customLocation[] = [
                'type' => 'Feature',
                'geometry' => [
                    'coordinates' => [
                        $location->long, $location->lat
                    ],
                    'type' => 'Point'
                ],
                'properties' => [
                    'message' => $location->description,
                    'iconSize' => [50,50],
                    'locationId' => $location->id,
                    'title' => $location->title,
                    'image' => $location->image,
                    'description' => $location->description
                ]
            ];
        };

        $geoLocations = [
            'type' => 'FeatureCollection',
            'features' => $customLocation
        ];  
        
        $geoJson = collect($geoLocations)->toJson();
        $this->geoJson = $geoJson;
    }
   
    public function render()
    {
        $this->getLocations();
        return view('livewire.my-test');
    }

    public function previewImage(){
        if(!$isEdit) {
            $this->validate([
                'image' => 'image|max:2048'
            ]);
        }        
    }

    public function store(){  
        $this->validate([
            'long' => 'required',
            'lat' => 'required',
            'title' => 'required',
            'description' => 'required',
            'image' => 'image|max:2048|required',
        ]);

        $imageName = md5($this->image.microtime()).'.'.$this->image->extension();

        Storage::putFileAs(
            'public/images',
            $this->image,
            $imageName
        );

        Marker::create([
            'long' => $this->long,
            'lat' => $this->lat,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $imageName,
            'user_id' => Auth::id(),
        ]);

        session()->flash('info', 'Product Created Successfully');

        $this->clearForm();
        $this->getLocations();    
        $this->dispatchBrowserEvent('locationAdded', $this->geoJson);        
    }

    public function update(){  
        $this->validate([
            'long' => 'required',
            'lat' => 'required',
            'title' => 'required',
            'description' => 'required',
        ]);

        $location = Marker::findOrFail($this->locationId);

        if($this->image){
            $imageName = md5($this->image.microtime()).'.'.$this->image->extension();

            Storage::putFileAs(
                'public/images',
                $this->image,
                $imageName
            );

            $updateData = [
                'title' => $this->title,
                'description' => $this->description,
                'image' => $imageName,
            ];
        }else{
            $updateData = [
                'title' => $this->title,
                'description' => $this->description,
            ];
        }   

        $location->update($updateData);

        session()->flash('info', 'Product Updated Successfully');

        $this->clearForm();
        $this->getLocations();    
        $this->dispatchBrowserEvent('updateLocation', $this->geoJson);        
    }

    public function deleteLocationById(){
        $location = Marker::findOrFail($this->locationId);
        $location->delete();

        $this->clearForm();
        $this->dispatchBrowserEvent('deleteLocation', $location->id);           
    }

    public function clearForm(){
        $this->long = '';
        $this->lat = '';
        $this->title = '';
        $this->description = '';
        $this->image = '';
        $this->imageUrl = '';
        $this->isEdit = false;
    }

    public function findLocationById($id){
        $location = Marker::findOrFail($id);

        $this->locationId = $id;
        $this->long = $location->long;
        $this->lat = $location->lat;
        $this->title = $location->title;
        $this->description = $location->description;
        $this->isEdit = true;
        $this->imageUrl = $location->image;
    }

}
