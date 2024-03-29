// ─────────────────────────────────────────────────────────────────────────────
// Main Entry File
// ─────────────────────────────────────────────────────────────────────────────
window._ = require('lodash');
window.axios = require('axios');
window.Promise = require('es6-promise').Promise;

// Custom Libraries
const libs = require('./libraries');

window.xhr = libs.xhr;
window.url = libs.url;
window.forms = libs.forms;


// ─────────────────────────────────────────────────────────────────────────────
// Axios Interceptor
//
// @important For 200 requests with data.result == 0 I throw an Exception and pass
// the JSON Response to the catch(err => .. method.
// ─────────────────────────────────────────────────────────────────────────────
require('./components/');

// ─────────────────────────────────────────────────────────────────────────────
// Document Ready
// ─────────────────────────────────────────────────────────────────────────────
$(() => {
  /**
   * ───────────────────────────────────────────────────────────────────────────
   * Set the users unique CSRF tokenKey/token for all Ajax Calls
   * ───────────────────────────────────────────────────────────────────────────
   */
  const csrfSelector = $('meta[name=\'csrf\']');
  const tokenKey = csrfSelector.attr('data-key');
  const token = csrfSelector.attr('data-token');

  /**
   * Call when DOM is loaded to pass the tokenKey and token
   *
   * @param  {string} tokenKey
   * @param  {string} token
   *
   * @return object axios
   */
  axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest',
  };
  axios.defaults.responseType = 'json';
});

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * @important Load these after Axios is configured.
 *
 * Load Pages and Elements
 * Every page has it's own Document Ready
 * ─────────────────────────────────────────────────────────────────────────────
 */
require('./pages/');
require('./global');

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Modernizr: SVG's
 * ─────────────────────────────────────────────────────────────────────────────
 */
if (!Modernizr.svg) {
  const imgs = $('img');
  const svgExtension = /.*\.svg$/;

  for (let i = 0; i < imgs.length; i++) {
    if (imgs[i].src.match(svgExtension)) {
      imgs[i].src = `${imgs[i].src.slice(0, -3)}png`;
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
