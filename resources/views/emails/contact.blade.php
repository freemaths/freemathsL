@component('mail::message')
A message from {{$sender}}:
@component('mail::panel')
@foreach (explode("\n",$message) as $line)
{{$line}}<br>
@endforeach
@endcomponent
Sent on behalf of {{$sender}} by {{config('app.name')}}<br>
@component('mail::button', ['url'=>config('app.url')."/mail/$token"])
View on FreeMaths.uk
@endcomponent
Use View on FreeMaths.uk to see formatted maths and reply.
@endcomponent
