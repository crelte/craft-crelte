# Dev

## How to setup this

Create file: .ddev/docker-compose.mounts.yaml

With the content:
```
services:
  web:
    volumes:
    - "$HOME/<craft-crelte-location>:/home/craft-crelte"
```

## Modify composer.json

```
"require": {
	"crelte/craft-crelte": "dev-main"
},
"repositories": [
	{
		"type": "path",
		"url": "/home/craft-crelte"
	}
],
```
