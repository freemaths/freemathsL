@component('mail::message')
A message from {{$sender}}:
@component('mail::panel')
@foreach (explode("\n",$message) as $line)
{{$line}}<br>
@endforeach
@endcomponent
Use View on FreeMaths.uk to see formatted maths and reply.
@component('mail::button', ['url'=>url("/mail/$token")])
View on FreeMaths.uk
@endcomponent

@endcomponent
