
@if(count($route['urlParameters']))
    <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
    @foreach($route['urlParameters'] as $attribute => $parameter)
        @component('scribe::components.field-details-postman', [
          'name' => $attribute,
          'type' => null,
          'required' => $parameter['required'] ?? true,
          'description' => $parameter['description'],
        ])
        @endcomponent
    @endforeach
@endif
@if(count($route['queryParameters']))
    <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
    @foreach($route['queryParameters'] as $attribute => $parameter)
        @component('scribe::components.field-details-postman', [
          'name' => $attribute,
          'type' => null,
          'required' => $parameter['required'] ?? true,
          'description' => $parameter['description'],
        ])
        @endcomponent
    @endforeach
@endif
@if(count($route['bodyParameters']))
    <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
    <br>
    @foreach($route['bodyParameters'] as $attribute => $parameter)
        @component('scribe::components.field-details-postman', [
          'name' => $attribute,
          'type' => $parameter['type'] ?? null,
          'required' => $parameter['required'] ?? true,
          'description' => $parameter['description'],
        ])
        @endcomponent
        <br>
    @endforeach
@endif
