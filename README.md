**myTDX** (my tiny todo extended)

** Use following nginx config file **
```
server {
        listen 4001 default_server;

        root /srv/http/myTDX;
        server_name _;
        autoindex off;

        proxy_intercept_errors on;
        index myTDX/index.php index.php index.html index.htm;

# deny access to hidden files and for instance Git directories
	location ~ /\.(?!well-known).* {
	    deny all;
	        access_log off;
		    log_not_found off;
}


        location ~ \.php$ {
                include fastcgi.conf;
                fastcgi_intercept_errors on;
                fastcgi_pass  127.0.0.1:9000;
                fastcgi_param SERVER_NAME \$host;
        }

        location ~ /\.ht {
                deny all;
        }
}
```


As the name suggests, this project is heavily based on an old but very well done ajax todolist
written by maxpozdeev/mytinytodo (http://www.mytinytodo.net/). It also works quite well on mobile phones.
The older project was resumed years after being frozen, and now the two variants have diverged significantly.

To see it in action ASAP, just rename "db_sample/" as "db/" to bypass the quick setup.

My fork adds some important features, especially for *sharing with others*, including developers.
I wanted a small, fast, no-nonsense bugtracking system which anyone can understand, including my
clients and the end users.

- most notably, tasks now have visible identifiers to refer to. There was no way to point to tasks
unambiguously (new in v1.5.3: an option in the Settings lets you hide them).

- in addition to existing URL, notes can also embed cross-references to other tasks (just use #taskid).
When you click on them you are redirected to the respective task.

- this cross-reference is done via an enhanced search. You can now look for "#123" or simply its number
to go to the respective task (and its notes will be opened). Looking for non-numerical text will search
the titles and notes.

- the incoming index URL also can provide the search string (use "?i=taskid" or "?s=keyword"). Thus,
pointing directly to a specific task is done with ?i=123. This is convenient in order to send links
by email, e.g. When you want all related tasks and cross-references you may prefer "?s=123"
There is a *very annoying bug* though, as URLs cannot point and open a task that is not in the first list.

- 1.5.2: you can write markdown in the notes (it can be deactivated in the settings)

- the styles of tags can be customized easily, in addition to a few special prefixes: =state, @user, !highlight.
I use them like "=acknoledged" or "=closed" for bug tracking, or "!discussion" to highlight the task, and
someone can visibly be assigned to a task with @joe.

- the tag list at the end of a task being edited now also shows tags *from other lists*. There is a setting
to change this, but it helps keep things tidy. The grayed tags are "borrowed" from the other lists.

- Finally, I added a backup system in case you are using sqlite (which I highly recommend here unless you
really have a reason to use mysql). The duration of backup files is set in the settings, but their restoration
must be done manually if ever something terrible happens (easy, it is one single file to rename).
