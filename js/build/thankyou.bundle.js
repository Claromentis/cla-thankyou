define(["jquery","cla_select2"],function(t,e){return function(t){function e(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return t[o].call(r.exports,r,r.exports,e),r.l=!0,r.exports}var n={};return e.m=t,e.c=n,e.i=function(t){return t},e.d=function(t,n,o){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:o})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="/intranet/thankyou/js/build/",e(e.s=3)}({"../../../node_modules/css-loader/index.js!../../../node_modules/sass-loader/lib/loader.js!./css/style.scss":function(t,e,n){e=t.exports=n("../../../node_modules/css-loader/lib/css-base.js")(),e.push([t.i,'.thank-you .thank-you-item {\n  list-style: none; }\n  .thank-you .thank-you-item:not(:first-of-type) {\n    margin-top: 20px; }\n\n.thank-you .thank-you-note {\n  background: #f5f5f5;\n  border-radius: 6px;\n  padding: 10px;\n  position: relative;\n  border: 1px solid #e9e9e9; }\n  .thank-you .thank-you-note hr {\n    margin: 8px 0;\n    border-color: #e7e7e7; }\n  .thank-you .thank-you-note:after, .thank-you .thank-you-note:before {\n    top: 100%;\n    content: " ";\n    border: solid transparent;\n    height: 0;\n    width: 0;\n    position: absolute;\n    pointer-events: none; }\n  .thank-you .thank-you-note:after {\n    border-color: transparent;\n    border-top-color: #f5f5f5;\n    border-width: 8px;\n    margin-left: -10px;\n    right: 10px; }\n  .thank-you .thank-you-note:before {\n    border-color: transparent;\n    border-top-color: #e9e9e9;\n    border-width: 10px;\n    margin-left: -8px;\n    right: 8px; }\n  .thank-you .thank-you-note .thankyou-link-border {\n    border-left: 1px solid #e7e7e7;\n    margin-left: 3px;\n    padding-left: 6px;\n    display: inline-block; }\n\n.thank-you .thank-you-meta {\n  margin-top: 15px;\n  text-align: right; }\n  .thank-you .thank-you-meta .author-photo {\n    width: 40px; }\n\n.thank-you .no-decoration:hover, .thank-you .no-decoration:focus {\n  text-decoration: none; }\n\n.thank-you .user-photo {\n  width: 26px;\n  margin-bottom: 3px; }\n\n.thank-you .thank-you-note a:nth-child(1) img {\n  margin-left: 8px; }\n\n.thank-you .js-like-component {\n  display: inline-block; }\n  .thank-you .js-like-component .liked.liked {\n    border-right: 1px solid #e7e7e7;\n    margin-right: 3px;\n    padding-right: 6px;\n    display: inline-block; }\n  .thank-you .js-like-component .glyphicons.glyphicons-thumbs-up {\n    width: 12px; }\n\n.thank-you .edit-tools {\n  display: block; }\n  .thank-you .edit-tools .edit-thanks {\n    border-right: 1px solid #e7e7e7;\n    margin-right: 3px;\n    padding-right: 6px; }\n  .thank-you .edit-tools .delete-thanks {\n    padding-left: 6px; }\n\n.panel .panel-heading .btn.pull-right.thank-you--button {\n  position: absolute;\n  right: 23px;\n  top: 14px; }\n\n.tile-thank-you.grid-stack-item-6[data-gs-width="1"] .thank-title {\n  display: none; }\n\n@media only screen and (max-width: 768px) {\n  .tile-thank-you .thank-title {\n    display: none; } }\n\n.comments-toggle-wrapper.comments-border {\n  border-left: 1px solid #e7e7e7;\n  margin-left: 3px;\n  padding-left: 6px;\n  display: inline-block; }\n  .comments-toggle-wrapper.comments-border .glyphicons-comments.glyphicons {\n    color: #595959; }\n\n.comments {\n  display: none; }\n\n.js-listable-item-admin-container .listable-item-admin-new {\n  background: greenyellow; }\n\n.js-listable-item-admin-container .listable-item-admin-modified {\n  background: #00A6C7; }\n\n.js-listable-item-admin-container .listable-item-admin-deleted {\n  background: red; }\n\n.js-listable-item-admin-container .lia-heading {\n  text-align: center; }\n',""])},"../../../node_modules/css-loader/lib/css-base.js":function(t,e){t.exports=function(){var t=[];return t.toString=function(){for(var t=[],e=0;e<this.length;e++){var n=this[e];n[2]?t.push("@media "+n[2]+"{"+n[1]+"}"):t.push(n[1])}return t.join("")},t.i=function(e,n){"string"==typeof e&&(e=[[null,e,""]]);for(var o={},r=0;r<this.length;r++){var i=this[r][0];"number"==typeof i&&(o[i]=!0)}for(r=0;r<e.length;r++){var a=e[r];"number"==typeof a[0]&&o[a[0]]||(n&&!a[2]?a[2]=n:n&&(a[2]="("+a[2]+") and ("+n+")"),t.push(a))}},t}},"../../../node_modules/style-loader/addStyles.js":function(t,e,n){function o(t,e){for(var n=0;n<t.length;n++){var o=t[n],r=f[o.id];if(r){r.refs++;for(var i=0;i<r.parts.length;i++)r.parts[i](o.parts[i]);for(;i<o.parts.length;i++)r.parts.push(d(o.parts[i],e))}else{for(var a=[],i=0;i<o.parts.length;i++)a.push(d(o.parts[i],e));f[o.id]={id:o.id,refs:1,parts:a}}}}function r(t){for(var e=[],n={},o=0;o<t.length;o++){var r=t[o],i=r[0],a=r[1],s=r[2],l=r[3],u={css:a,media:s,sourceMap:l};n[i]?n[i].parts.push(u):e.push(n[i]={id:i,parts:[u]})}return e}function i(t,e){var n=y(t.insertInto);if(!n)throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");var o=v[v.length-1];if("top"===t.insertAt)o?o.nextSibling?n.insertBefore(e,o.nextSibling):n.appendChild(e):n.insertBefore(e,n.firstChild),v.push(e);else{if("bottom"!==t.insertAt)throw new Error("Invalid value for parameter 'insertAt'. Must be 'top' or 'bottom'.");n.appendChild(e)}}function a(t){t.parentNode.removeChild(t);var e=v.indexOf(t);e>=0&&v.splice(e,1)}function s(t){var e=document.createElement("style");return t.attrs.type="text/css",u(e,t.attrs),i(t,e),e}function l(t){var e=document.createElement("link");return t.attrs.type="text/css",t.attrs.rel="stylesheet",u(e,t.attrs),i(t,e),e}function u(t,e){Object.keys(e).forEach(function(n){t.setAttribute(n,e[n])})}function d(t,e){var n,o,r;if(e.singleton){var i=k++;n=g||(g=s(e)),o=p.bind(null,n,i,!1),r=p.bind(null,n,i,!0)}else t.sourceMap&&"function"==typeof URL&&"function"==typeof URL.createObjectURL&&"function"==typeof URL.revokeObjectURL&&"function"==typeof Blob&&"function"==typeof btoa?(n=l(e),o=h.bind(null,n,e),r=function(){a(n),n.href&&URL.revokeObjectURL(n.href)}):(n=s(e),o=c.bind(null,n),r=function(){a(n)});return o(t),function(e){if(e){if(e.css===t.css&&e.media===t.media&&e.sourceMap===t.sourceMap)return;o(t=e)}else r()}}function p(t,e,n,o){var r=n?"":o.css;if(t.styleSheet)t.styleSheet.cssText=x(e,r);else{var i=document.createTextNode(r),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(i,a[e]):t.appendChild(i)}}function c(t,e){var n=e.css,o=e.media;if(o&&t.setAttribute("media",o),t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}function h(t,e,n){var o=n.css,r=n.sourceMap,i=void 0===e.convertToAbsoluteUrls&&r;(e.convertToAbsoluteUrls||i)&&(o=b(o)),r&&(o+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */");var a=new Blob([o],{type:"text/css"}),s=t.href;t.href=URL.createObjectURL(a),s&&URL.revokeObjectURL(s)}var f={},m=function(t){var e;return function(){return void 0===e&&(e=t.apply(this,arguments)),e}}(function(){return window&&document&&document.all&&!window.atob}),y=function(t){var e={};return function(n){return void 0===e[n]&&(e[n]=t.call(this,n)),e[n]}}(function(t){return document.querySelector(t)}),g=null,k=0,v=[],b=n("../../../node_modules/style-loader/fixUrls.js");t.exports=function(t,e){if("undefined"!=typeof DEBUG&&DEBUG&&"object"!=typeof document)throw new Error("The style-loader cannot be used in a non-browser environment");e=e||{},e.attrs="object"==typeof e.attrs?e.attrs:{},void 0===e.singleton&&(e.singleton=m()),void 0===e.insertInto&&(e.insertInto="head"),void 0===e.insertAt&&(e.insertAt="bottom");var n=r(t);return o(n,e),function(t){for(var i=[],a=0;a<n.length;a++){var s=n[a],l=f[s.id];l.refs--,i.push(l)}if(t){o(r(t),e)}for(var a=0;a<i.length;a++){var l=i[a];if(0===l.refs){for(var u=0;u<l.parts.length;u++)l.parts[u]();delete f[l.id]}}}};var x=function(){var t=[];return function(e,n){return t[e]=n,t.filter(Boolean).join("\n")}}()},"../../../node_modules/style-loader/fixUrls.js":function(t,e){t.exports=function(t){var e="undefined"!=typeof window&&window.location;if(!e)throw new Error("fixUrls requires window.location");if(!t||"string"!=typeof t)return t;var n=e.protocol+"//"+e.host,o=n+e.pathname.replace(/\/[^\/]*$/,"/");return t.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi,function(t,e){var r=e.trim().replace(/^"(.*)"$/,function(t,e){return e}).replace(/^'(.*)'$/,function(t,e){return e});if(/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/)/i.test(r))return t;var i;return i=0===r.indexOf("//")?r:0===r.indexOf("/")?n+r:o+r.replace(/^\.\//,""),"url("+JSON.stringify(i)+")"})}},"./css/style.scss":function(t,e,n){var o=n("../../../node_modules/css-loader/index.js!../../../node_modules/sass-loader/lib/loader.js!./css/style.scss");"string"==typeof o&&(o=[[t.i,o,""]]);n("../../../node_modules/style-loader/addStyles.js")(o,{});o.locals&&(t.exports=o.locals)},"./js/src/thankyou.js":function(t,e,n){var o,r;o=[n(0),n(1),n("./css/style.scss")],void 0!==(r=function(t){var e=function(){this.modal=t("#thank_you_modal"),this.form=t(".js-thank_you-form"),this.delete_form=t(".js-thank_you-delete-form"),this.form_error_template=this.modal.find(".js-form-error-template"),this.tags_config={width:"100%",allowClear:!0,ajax:{url:"/api/thankyou/v2/tags",dataType:"json",data:function(t){return{name:t.term,limit:4}},processResults:function(t){var e=[];for(var n in t)e.push({id:n,text:t[n].name});return{results:e}}}},this.configureTags(),t(".js-thank-you-create").on("click",function(){var e=null,o=t(this).attr("data-preselected_thanked");"string"==typeof o&&(e=JSON.parse(o)),n.create(e)}),t(".js-thank_you-edit-button").on("click",function(){n.edit(t(this).attr("data-id"))}),t(".js-thank_you-delete-button").on("click",function(){n.delete(t(this).attr("data-id"))}),this.form.on("submit",null,this,function(t){t.preventDefault(),t.data.submit()}),this.delete_form.on("submit",null,this,function(t){t.preventDefault(),t.data.submitDelete()})};e.prototype.configureTags=function(){this.form.find('select[name="thank_you_tags[]"]').select2(this.tags_config)},e.prototype.create=function(t){this.resetForm(),"object"==typeof t&&null!==t&&(this.setThanked([t]),this.lockThanked(!0)),this.showModal(!0)},e.prototype.edit=function(e){var n=this;t.ajax("/api/thankyou/v2/thanks/"+e+"?thanked=1&tags=1",{success:function(t){n.populateForm(t),n.lockThanked(!1),n.showModal(!0)}})},e.prototype.delete=function(t){this.setDeleteID(t),this.showDeleteModal(!0)},e.prototype.resetForm=function(){this.resetErrors(),this.setThankYouID(null),this.setThanked(null),this.setTags(null),this.setDescription(null)},e.prototype.populateForm=function(t){this.resetErrors(),this.setThankYouID(t.id),"thanked"in t&&this.setThanked(t.thanked),"tags"in t&&this.setTags(t.tags),this.setDescription(t.description)},e.prototype.getThankYouIDInput=function(){return this.form.find('input[name="id"]')},e.prototype.getThankedInput=function(){return this.form.find('select[name="thank_you_user[]"]')},e.prototype.getTagsInput=function(){return this.form.find('select[name="thank_you_tags[]"]')},e.prototype.getDescriptionInput=function(){return this.form.find('textarea[name="thank_you_description"]')},e.prototype.getDeleteIDInput=function(){return this.delete_form.find('input[name="id"]')},e.prototype.setThankYouID=function(t){this.getThankYouIDInput().val(t)},e.prototype.setThanked=function(t){var e=this.getThankedInput();if(e.val(null),e.html(null),"object"==typeof t)for(var n in t)cla_multi_object_picker.addOption(t[n].object_type.id,t[n].id,t[n].object_type.name+": "+t[n].name,e.attr("id"));e.trigger("change")},e.prototype.setTags=function(t){var e=this.getTagsInput();if(e.val(null),e.html(null),"object"==typeof t)for(var n in t)e.append('<option selected value="'+t[n].id+'">'+t[n].name+"</option>");e.trigger("change")},e.prototype.setDescription=function(t){this.getDescriptionInput().val(t)},e.prototype.setPreselected=function(t){this.form.find(".js-thank_you-thanked-names").text(t)},e.prototype.setDeleteID=function(t){this.getDeleteIDInput().val(t)},e.prototype.showModal=function(t){!0===t?this.modal.modal("show"):this.modal.modal("hide")},e.prototype.showDeleteModal=function(e){var n=t("#thank_you_delete_modal");!0===e?n.modal("show"):n.modal("hide")},e.prototype.lockThanked=function(t){var e=this.getThankedInput();if(t){this.displayPicker(!1);var n="",o=cla_multi_object_picker.GetSelected(e);for(var r in o)n+=o[r].text();this.setPreselected(n),this.displayThankedNames(!0)}else this.displayThankedNames(!1),this.setPreselected(""),this.displayPicker(!0)},e.prototype.displayPicker=function(t){var e=this.form.find(".js-thank_you-picker-container");!0===t?e.show():e.hide()},e.prototype.displayThankedNames=function(t){var e=this.form.find(".js-thank_you-thanked-names-container");!0===t?e.show():e.hide()},e.prototype.submit=function(){var e=this;e.resetErrors();var n=e.getThankYouIDInput().val();""===n&&(n=null);var o=e.getThankedInput().val(),r=e.getTagsInput().val(),i=e.getDescriptionInput().val(),a={description:i};if("object"==typeof o){var s=[];for(var l in o){var u=o[l].split("_");s.push({oclass:parseInt(u[0]),id:parseInt(u[1])})}a.thanked=s}if("object"==typeof r&&null!==r){var d=[];for(var l in r)d.push(parseInt(r[l]));a.tags=d}var p="/api/thankyou/v2/thankyou";null!==n&&(p+="/"+n),t.ajax({url:p,type:"POST",dataType:"json",contentType:"application/json",data:JSON.stringify(a),error:function(t){var n=t.responseJSON,o=e.form.find(".js-form-error"),r=o.filter('[data-name="problem_details-title"]');if(r.length>0&&"title"in n&&e.addError(r,n.title),"invalid-params"in n)for(var i in n["invalid-params"]){var a=n["invalid-params"][i];if("name"in a){var s=o.filter('[data-name="'+a.name+'"]');s.length>0&&"reason"in a&&e.addError(s,a.reason)}}},success:function(t){location.reload()}})},e.prototype.resetErrors=function(){this.form.find(".js-form-error").empty()},e.prototype.addError=function(e,n){var o=t(_.template(this.form_error_template.html())({}));o.text(n),e.append(o)},e.prototype.submitDelete=function(){var e=this,n=e.getDeleteIDInput().val();t.ajax({url:"/api/thankyou/v2/thankyou/"+n,type:"DELETE",error:function(t){var n=t.responseJSON,o=e.delete_form.find(".js-form-error"),r=o.filter('[data-name="problem_details-title"]');r.length>0&&"title"in n&&e.addError(r,n.title)},success:function(t){location.reload()}})};var n=new e;return n}.apply(e,o))&&(t.exports=r)},0:function(e,n){e.exports=t},1:function(t,n){t.exports=e},3:function(t,e,n){t.exports=n("./js/src/thankyou.js")}})});