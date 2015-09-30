'use strict';

var Modal = {
	modal : null,

	showMask : function() {
		var mask = document.createElement('div');
		mask.style.opacity = 0.8;
		mask.style.width = '100%';
		mask.style.height = '100%';
		mask.style.position = 'fixed';
		mask.style.top = 0;
		mask.style.left = 0;
		mask.style.zIndex = 1000;
		mask.style.backgroundColor = 'black';
		document.body.appendChild(mask);
	},

	hideMask : function() {
		document.body.removeChild(document.body.lastChild);
	},

	manageEscape : function(event) {
		//close modal if escape is pressed
		if(event.keyCode === 27) {
			Modal.close();
		}
	},

	manageOutsideClick : function(event) {
		//close modal if a clic occurs outside
		var node = event.target;
		while(node.parentNode != null) {
			if(node.parentNode === Modal.modal) {
				return;
			}
			node = node.parentNode;
		}
		Modal.close();
	},

	open : function(event) {
		var link = this.getAttribute('href');
		//modal window is already here, hidden in the page
		if(link.indexOf('#') !== -1) {
			link = link.substring(link.indexOf('#') + 1, link.length);
			Modal.show(document.getElementById(link));
		}
		//modal content must be loaded from network
		else {
			//add timestamp parameter to be sure the browser won't use its cache
			link += link.indexOf('?') != -1 ? '&' : '?';
			link = link + 'ts=' + Math.floor(Math.random() * 1000001);
			//retrieve data
			var xhr = new XMLHttpRequest();
			xhr.addEventListener('load', function() {
				if(xhr.readyState === 4) {
					if(xhr.status === 200) {
						var xml = xhr.responseXML;
						var modal = document.createElement('div');
						//add content from xhr
						for(var i = 0; i < xml.childNodes.length; i++) {
							if(xml.childNodes[i].nodeType === Node.ELEMENT_NODE || xml.childNodes[i].nodeType === Node.TEXT_NODE) {
								modal.appendChild(document.importNode(xml.childNodes[i], true));
							}
						}
						//add close button
						var close = document.createElement('button');
						close.addEventListener('click', function() {
							document.body.removeChild(Modal.modal);
							Modal.close()
						}, true);
						close.textContent = 'Close';
						close.style.cssFloat = 'right';
						close.style.margin = '10px';
						close.style.display = 'block';
						modal.appendChild(close);
						//add modal
						document.body.appendChild(modal);
						Modal.show(modal);
					}
				}
			});
			xhr.open('GET', link, true);
			xhr.send(null);
		}
		event.stopPropagation();
		event.preventDefault();
	},

	show : function(element) {
		if(Modal.modal) {
			return;
		}
		Modal.showMask();
		Modal.modal = element;
		Modal.modal.style.position = 'fixed';
		Modal.modal.style.top = '20%';
		Modal.modal.style.left = '10%';
		Modal.modal.style.display = 'block';
		Modal.modal.style.width = '80%';
		Modal.modal.style.zIndex = 1001;
		Modal.modal.style.opacity = 1;
		Modal.modal.style.backgroundColor = 'white';
		document.addEventListener('keypress', Modal.manageEscape, false);
		document.addEventListener('click', Modal.manageOutsideClick, false);
	},

	close : function() {
		document.removeEventListener('click', Modal.manageOutsideClick, false);
		document.removeEventListener('keypress', Modal.manageEscape, false);
		Modal.modal.style.display = 'none';
		Modal.modal = null;
		Modal.hideMask();
	}
}