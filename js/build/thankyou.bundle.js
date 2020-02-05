define(["cla_select2","jquery"],function(t,e){return function(t){function e(o){if(n[o])return n[o].exports;var i=n[o]={i:o,l:!1,exports:{}};return t[o].call(i.exports,i,i.exports,e),i.l=!0,i.exports}var n={};return e.m=t,e.c=n,e.i=function(t){return t},e.d=function(t,n,o){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:o})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="/intranet/thankyou/js/build/",e(e.s=2)}({"./js/src/thankyou.js":function(t,e,n){var o,i;o=[n(1),n(0)],void 0!==(i=function(t){var e=function(t){this.list=t,this.modal=t.find(".js-thank_you-modal").first(),this.form=this.modal.find(".js-thank_you-form"),this.delete_modal=t.find(".js-thank_you-delete-modal"),this.delete_form=this.delete_modal.find(".js-thank_you-delete-form"),this.form_error_template=_.template(this.modal.find(".js-form-error-template").html()),this.tags_config={width:"100%",allowClear:!0,ajax:{url:"/api/thankyou/v2/tags",dataType:"json",data:function(t){return{name:t.term,active:!0,limit:4}},processResults:function(t){var e=[];for(var n in t)e.push({id:n,text:t[n].name});return{results:e}}},placeholder:lmsg("thankyou.common.tags.multiselect")},this.configureTags(),this.registerEventListeners()};return e.prototype.configureTags=function(){this.form.find('select[name="thank_you_tags[]"]').select2(this.tags_config)},e.prototype.create=function(t){this.resetForm(),"object"==typeof t&&null!==t&&(this.setThanked(t),this.lockThanked(!0)),this.showModal(!0)},e.prototype.edit=function(e){var n=this;t.ajax("/api/thankyou/v2/thanks/"+e+"?thanked=1&tags=1",{success:function(t){n.populateForm(t),n.lockThanked(!1),n.showModal(!0)}})},e.prototype.delete=function(t){this.setDeleteID(t),this.showDeleteModal(!0)},e.prototype.resetForm=function(){this.resetErrors(),this.setThankYouID(null),this.setThanked(null),this.setTags(null),this.setDescription(null)},e.prototype.populateForm=function(t){this.resetErrors(),this.setThankYouID(t.id),"thanked"in t&&this.setThanked(t.thanked),"tags"in t&&this.setTags(t.tags),this.setDescription(t.description)},e.prototype.getThankYouIDInput=function(){return this.form.find('input[name="id"]')},e.prototype.getThankedInput=function(){return this.form.find('select[name="thank_you_user[]"]')},e.prototype.getTagsInput=function(){return this.form.find('select[name="thank_you_tags[]"]')},e.prototype.getDescriptionInput=function(){return this.form.find('textarea[name="thank_you_description"]')},e.prototype.getDeleteIDInput=function(){return this.delete_form.find('input[name="id"]')},e.prototype.setThankYouID=function(t){this.getThankYouIDInput().val(t)},e.prototype.setThanked=function(t){var e=this.getThankedInput();if(e.val(null),e.html(null),"object"==typeof t)for(var n in t)cla_multi_object_picker.addOption(t[n].object_type.id,t[n].id,t[n].object_type.name+": "+t[n].name,e.attr("id"));e.trigger("change")},e.prototype.setTags=function(t){var e=this.getTagsInput();if(e.val(null),e.html(null),"object"==typeof t)for(var n in t)e.append('<option selected value="'+t[n].id+'">'+t[n].name+"</option>");e.trigger("change")},e.prototype.setDescription=function(t){this.getDescriptionInput().val(t)},e.prototype.setDescriptionMaxLength=function(t){this.getDescriptionInput().attr("maxlength",t)},e.prototype.setPreselected=function(t){this.form.find(".js-thank_you-thanked-names").text(t)},e.prototype.setDeleteID=function(t){this.getDeleteIDInput().val(t)},e.prototype.showModal=function(t){!0===t?this.modal.modal("show"):this.modal.modal("hide")},e.prototype.showDeleteModal=function(t){!0===t?this.delete_modal.modal("show"):this.delete_modal.modal("hide")},e.prototype.lockThanked=function(t){var e=this.getThankedInput();if(t){this.displayPicker(!1);var n="",o=cla_multi_object_picker.GetSelected(e);for(var i in o)n+=o[i].text();this.setPreselected(n),this.displayThankedNames(!0)}else this.displayThankedNames(!1),this.setPreselected(""),this.displayPicker(!0)},e.prototype.displayPicker=function(t){var e=this.form.find(".js-thank_you-picker-container");!0===t?e.show():e.hide()},e.prototype.displayThankedNames=function(t){var e=this.form.find(".js-thank_you-thanked-names-container");!0===t?e.show():e.hide()},e.prototype.submit=function(){t(".btn-submit-modal").prop("disabled",!0);var e=this;e.resetErrors();var n=e.getThankYouIDInput().val();""===n&&(n=null);var o=e.getThankedInput().val(),i=e.getTagsInput().val(),r=e.getDescriptionInput().val(),a={description:r};if("object"==typeof o){var s=[];for(var l in o){var u=o[l].split("_");s.push({oclass:parseInt(u[0]),id:parseInt(u[1])})}a.thanked=s}if("object"==typeof i&&(a.tags=[],null!==i))for(var l in i)a.tags.push(parseInt(i[l]));var p="/api/thankyou/v2/thankyou";null!==n&&(p+="/"+n),t.ajax({url:p,type:"POST",dataType:"json",contentType:"application/json",data:JSON.stringify(a),error:function(n){t(".btn-submit-modal").prop("disabled",!1);var o=n.responseJSON,i=e.form.find(".js-form-error"),r=i.filter('[data-name="problem_details-title"]'),a=!1;if("invalid-params"in o)for(var s in o["invalid-params"]){var l=o["invalid-params"][s];if("name"in l){var u=i.filter('[data-name="'+l.name+'"]');u.length>0&&"reason"in l&&(e.addError(u,l.reason),a=!0)}}else r.length>0&&"title"in o&&!a&&e.addError(r,o.title)},success:function(t){window.location.reload()}})},e.prototype.resetErrors=function(){this.form.find(".js-form-error").empty()},e.prototype.addError=function(e,n){var o=t(this.form_error_template({message:n}));e.html(o)},e.prototype.submitDelete=function(){var e=this,n=e.getDeleteIDInput().val();t.ajax({url:"/api/thankyou/v2/thankyou/"+n,type:"DELETE",error:function(t){var n=t.responseJSON,o=e.delete_form.find(".js-form-error"),i=o.filter('[data-name="problem_details-title"]');i.length>0&&"title"in n&&e.addError(i,n.title)},success:function(t){location.reload()}})},e.prototype.registerEventListeners=function(){var e=this;this.list.on("click",".js-thank-you-create",function(){var n=null,o=t(this).attr("data-preselected_thanked");"string"==typeof o&&(n=JSON.parse(o)),e.create(n)}),this.list.on("click",".js-thank_you-edit-button",function(){e.edit(t(this).attr("data-id"))}),this.list.on("click",".js-thank_you-delete-button",function(){e.delete(t(this).attr("data-id"))}),this.list.on("click",".js-comments-reveal",function(e){t(e.target).closest(".js-thank-you").find(".js-comments").toggle()}),this.form.on("submit",null,this,function(t){t.preventDefault(),t.data.submit()}),this.delete_form.on("submit",null,this,function(t){t.preventDefault(),t.data.submitDelete()})},e}.apply(e,o))&&(t.exports=i)},0:function(e,n){e.exports=t},1:function(t,n){t.exports=e},2:function(t,e,n){t.exports=n("./js/src/thankyou.js")}})});