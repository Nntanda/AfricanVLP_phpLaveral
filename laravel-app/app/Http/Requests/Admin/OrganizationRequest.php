<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $organizationId = $this->route('organization')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'name')->ignore($organizationId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/',
            ],
            'website' => [
                'nullable',
                'string',
                'url',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
            ],
            'city_id' => [
                'nullable',
                'integer',
                'exists:cities,id',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:category_of_organizations,id',
            ],
            'institution_type_id' => [
                'nullable',
                'integer',
                'exists:institution_types,id',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive', 'pending', 'suspended']),
            ],
            'facebook_url' => [
                'nullable',
                'string',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?facebook\.com\/.*/',
            ],
            'twitter_url' => [
                'nullable',
                'string',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?twitter\.com\/.*/',
            ],
            'linkedin_url' => [
                'nullable',
                'string',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?linkedin\.com\/.*/',
            ],
            'instagram_url' => [
                'nullable',
                'string',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?instagram\.com\/.*/',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:2048', // 2MB
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.unique' => 'An organization with this name already exists.',
            'phone_number.regex' => 'The phone number format is invalid.',
            'website.url' => 'The website must be a valid URL.',
            'facebook_url.regex' => 'The Facebook URL must be a valid Facebook page URL.',
            'twitter_url.regex' => 'The Twitter URL must be a valid Twitter profile URL.',
            'linkedin_url.regex' => 'The LinkedIn URL must be a valid LinkedIn page URL.',
            'instagram_url.regex' => 'The Instagram URL must be a valid Instagram profile URL.',
            'logo.max' => 'The logo may not be greater than 2MB.',
            'latitude.between' => 'The latitude must be between -90 and 90.',
            'longitude.between' => 'The longitude must be between -180 and 180.',
            'status.in' => 'The status must be active, inactive, pending, or suspended.',
        ]);
    }
}