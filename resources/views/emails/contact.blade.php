@component('mail::message')
A message from {{$message->from->name}}{{$message->to->email==='ed@darnell.org.uk'?' <'.$message->from->email.'>':''}}<br>
@if ($question)
#Question: {{$question}}<br>
@endif
@component('mail::panel')
@foreach (explode("\n",$message->message) as $line)
{{$line}}<br>
@endforeach
@endcomponent
@if (isset($message->photo))
Message includes photo.
@endif
Use View on FreeMaths.uk to see formatted maths and reply.
@component('mail::button', ['url'=>url("?mail=$token")])
View on FreeMaths.uk
@endcomponent
@endcomponent
