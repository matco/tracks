'use strict';

var Toggle = {
	init : function() {
		Toggle.initNode(document);
	},

	initNode : function(node) {
		//add listeners on all nodes that have a toggle attribute
		var nodes = node.querySelectorAll('*[toggle]');
		for(var i = 0; i < nodes.length; i++) {
			Toggle.add(nodes[i]);
		}
	},

	add : function(node) {
		Toggle.addToggle(node, document.getElementById(node.getAttribute('toggle')));
	},

	addToggle : function(node, toggle) {
		if(!toggle.hasAttribute('id')) {
			toggle.id = new Date().getTime();
		}
		node.style.cursor = 'pointer';
		node.setAttribute('toggle', toggle.id);
		node.addEventListener('click', Toggle.toggle);
	},

	remove : function(node) {
		node.removeEventListener('click', Toggle.toggle);
	},

	toggle : function() {
		var toggle = document.getElementById(this.getAttribute('toggle'));
		toggle.style.display = toggle.style.display === 'none' ? 'block' : 'none';
	}
}

//auto add toggle on nodes which have a toggle attribute
window.addEventListener('load', Toggle.init);
