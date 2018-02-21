<!DOCTYPE html>
<html>
<head>
	<title>{{put[header]}}</title>
</head>
<body>
	<h1>{{put[header]}}</h1>
	<p>I have a few things:</p>
	<ul>
	{{foreach things as item}}
		<li>
			{{put [item>val, " has a few things of its own:"]}}
			<ul>
				{{foreach item*get_data as inner}}
					<li>{{put [inner]}}</li>
				{{endforeach}}
			</ul>
		</li>
	{{endforeach}}
	<ul>
	<p>I also have some words!!!</p>
	{{foreach words as word}}
	<p><b>{{put [word]}}</b></p>
	{{endforeach}}
	<p>Finally I also have a number, {{put [number]}} which: 
	{{if number > 10 then 
		put ["is larger than 10"]
		if number > 100 then
			put [" and is also larger than 100"]
			if number > 1000 then 
				put [" and is larger than 1000"]
			else
				put [" but is not larger than 1000"]
			endif
		else
			put [" but is not larger than 100"]
		endif
	else
		put ["is not larger than 10 "]
	endif
	}}
	<hr />
	<h2>Now I will import a template with no data for each thing...</h2>
	{{foreach things as item import "imported-template-none.tpl" [] endforeach}}
	<h2>Now I will import a template with all symbols for each thing...</h2>
	{{foreach things as item import "imported-template-all.tpl" [*] endforeach}}
	<h2>Now I will import a template with some symbols for each thing... I can also resolve the template file name!</h2>
	{{foreach things as item import templatefilenamesymbols [item as local, "cosa" as constant] endforeach}}
</body>
</html>
