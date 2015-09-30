'use strict';

window.addEventListener('load', initialisation);

var url = window.location.href.substring(0, window.location.href.lastIndexOf('/'));
var processor = new XSLTProcessor();

var search = '';
var offset = 0;
var count = 10;
var field = 'name';
var direction = 'asc';
var noted = true;

function resolver() {
	return 'http://www.w3.org/1999/xhtml';
}

//initialize parameters and listeners
function initialisation() {
	//load xslt
	var xhrxsl = new XMLHttpRequest();
	xhrxsl.onreadystatechange = function(event) {
		if(xhrxsl.readyState === 4 && xhrxsl.status === 200) {
			processor.importStylesheet(xhrxsl.responseXML);
		}
	};
	xhrxsl.open('GET', 'public/music.xsl', true);
	xhrxsl.responseType = 'document';
	xhrxsl.send();

	//notation management
	var nodes = document.querySelectorAll('#notation img');
	for(var i = 0; i < nodes.length; i++) {
		nodes[i].addEventListener(
			'mouseover',
			function(event) {
				if(!noted) {
					show_note(this.id.substring(5, 6));
				}
			}
		);
		nodes[i].addEventListener('click', vote);
	}
	show_note(1);

	//random button
	document.getElementById('refresh').addEventListener('click', random);

	//search preparation
	//form preparation
	document.getElementById('search').addEventListener(
		'submit',
		function(event) {
			search = document.getElementById('search').search.value;
			offset = 0;
			load();
			event.preventDefault();
			event.stopPropagation();
		}
	);

	monitor();
}

function monitor() {
	var entry = document.getElementById('search').search.value;
	if(entry != '' && entry != search) {
		offset = 0;
		search = entry;
		load();
	}
	setTimeout(monitor, 100);
}

function load() {
	document.getElementById('loading').style.visibility = 'visible';

	//loading xml file
	var xhrxml = new XMLHttpRequest();
	xhrxml.onreadystatechange = function(event) {
		if(xhrxml.readyState === 4 && xhrxml.status === 200) {
			var xml = xhrxml.responseXML;
			processor.setParameter(null, 'field', field);
			processor.setParameter(null, 'direction', direction);
			var fragment = processor.transformToFragment(xml, document);

			//adding new content
			while(document.getElementById('results').hasChildNodes()) {
				document.getElementById('results').removeChild(document.getElementById('results').firstChild);
			}
			document.getElementById('results').appendChild(fragment);

			//control bar creation
			var number = xml.firstChild.getAttribute('number')
			if(number > count) {
				if(offset >= count) {
					document.getElementById('previous').style.opacity = '1';
					document.getElementById('previous').setAttribute('href', 'previous');
					document.getElementById('previous').addEventListener(
						'click',
						function(event) {
							offset -= count;
							load();
							event.preventDefault();
							event.stopPropagation();
						}
					);
				}
				if(offset + count <= number) {
					document.getElementById('next').style.opacity = '1';
					document.getElementById('next').setAttribute('href', 'next');
					document.getElementById('next').addEventListener(
						'click',
						function(event) {
							offset += count;
							load();
							event.preventDefault();
							event.stopPropagation();
						}
					);
				}
			}

			//managing events on tracks
			var nodes = document.evaluate('//xhtml:tbody//xhtml:a', document, resolver, XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE, null);
			for(var i = 0; i < nodes.snapshotLength; i++) {
				nodes.snapshotItem(i).addEventListener(
					'click',
					function(event) {
						event.preventDefault();
						event.stopPropagation();
						play(this.getAttribute('href'), this.textContent, this.getAttribute('class'));
					}
				);
			}

			//add sorting features
			var nodes = document.evaluate('//xhtml:thead//xhtml:a[@class="sortable"]', document, resolver, XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE, null);
			for(var i = 0; i < nodes.snapshotLength; i++) {
				nodes.snapshotItem(i).addEventListener(
					'click',
					function(event) {
						direction = direction === 'asc' ? 'desc' : 'asc';
						field = this.id;
						load();
						event.preventDefault();
						event.stopPropagation();
					}
				);
			}

			document.getElementById('loading').style.visibility = 'hidden';
		}
	};
	var query = 'index.php?page=player&action=player:search&aargs=' + search + ',' + field + ',' + direction + ',' + count + ',' + offset + '&xhr=1';
	xhrxml.open('GET', query, true);
	xhrxml.responseType = 'document';
	xhrxml.send();
}

function vote(event) {
	event.preventDefault();
	event.stopPropagation();
	if(!noted) {
		noted = true;
		var id = document.getElementById('playing').getAttribute('track');
		var note = this.id.substring(5, 6)
		show_note(note);
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange = function(event) {
			if(xhr.readyState === 4 && xhr.status === 200) {
				notify('Thank you for your vote');
			}
		};
		xhr.open('GET', url + '/player-vote-' + id + ',' + note, true);
		xhr.send();
	}
};

function show_note(note) {
	for(var i = 1; i <= note; i++) {
		document.getElementById('star_' + i).src = 'public/icons/star.png';
		document.getElementById('star_' + i).style.opacity = '1';
	}
	note++;
	for(var i = note; i <= 5; i++) {
		document.getElementById('star_' + i).src = 'public/icons/star_white.png';
		document.getElementById('star_' + i).style.opacity = '1';
	}
}

function play(id, name, note) {
	var player = document.getElementById('player');
	var playing = document.getElementById('playing');

	//add current track in history if any
	if(playing.textContent) {
		var track = document.createElement('li');
		track.setAttribute('track', playing.getAttribute('track'));
		track.textContent = playing.textContent;
		track.setAttribute('note', playing.getAttribute('note'));
		track.addEventListener(
			'click',
			function() {
				play(this.getAttribute('track'), this.textContent, this.getAttribute('note'));
			}
		);
		var history = document.getElementById('history');
		history.style.display = 'block';
		var history_list = history.querySelector('ul');
		history_list.insertBefore(track, history_list.firstChild);
	}

	//show note and allow vote
	show_note(note);
	noted = false;

	//player
	player.src = url + '/player-provide-' + id;

	//set name
	playing.setAttribute('track', id);
	playing.setAttribute('note', note);
	playing.textContent = name;

	//highlight player
	document.getElementById('player_bar').style.opacity = '1';

	//notify user
	notify('Playing ' + name);
}

function random(event) {
	event.preventDefault();
	event.stopPropagation();
	var xhrxml = new XMLHttpRequest();
	xhrxml.onreadystatechange = function(event) {
		if(xhrxml.readyState === 4 && xhrxml.status === 200) {
			var xml = xhrxml.responseXML;
			play(xml.firstChild.firstChild.textContent, xml.firstChild.childNodes[1].textContent, xml.firstChild.childNodes[2].textContent);
		}
	};
	xhrxml.open('GET', url + '/player-random', true);
	xhrxml.responseType = 'document';
	xhrxml.send();
}

function notify(message) {
	if(Notification.permission === 'granted') {
		new Notification(message);
	}
	else if(Notification.permission !== 'denied') {
		Notification.requestPermission(function(permission) {
			if(permission === 'granted') {
				notify(message);
			}
		});
	}
}
