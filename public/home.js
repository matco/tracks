'use strict';

window.addEventListener('load', initialisation);

//initialize parameters and listeners
function initialisation() {
	document.getElementById('top').addEventListener('click', Modal.open);
}
