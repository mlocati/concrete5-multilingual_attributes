/* jshint unused:vars, undef:true, browser:true, jquery:true */
/* global tinyMCE */

if(!window.multilingual_attribute_mceWorkaround) { (function(window, $) {

function couple($textarea, realID) {
	var me = this;
	me.$textarea = $textarea;
	$textarea.before(me.$final = $('<input type="hidden" name="' + realID + '" id="' + realID + '" />').val($textarea.val()));
	var form = $textarea.closest('form')[0];
	var id = $textarea.attr('id');
	couple.all.push(me);
	var lookForMCE = function(cb) {
		var mce = null;
		if(window.tinyMCE) {
			$.each(tinyMCE.get(), function() {
				if(this.initialized) {
					var $form = $(this.contentAreaContainer).closest('form');
					if($form.length && ($form[0] === form) && (this.id === id)) {
						mce = this;
						return false;
					}
				}
			});
		}
		if(mce) {
			cb(mce);
		}
		else {
			setTimeout(function() {lookForMCE(cb); }, 100);
		}
	};
	lookForMCE(function(mce) {
		me.mce = mce;
		me.mce.onChange.add(function(ed, l) {
			me.$final.val(mce.getContent());
		});
		me.mce.onRemove.add(function(ed) {
			$.each(couple.all, function(i, c) {
				if(c === me) {
					couple.all.splice(i, 1);
					return false;
				}
			});
		});
	});
}
couple.all = [];

window.multilingual_attribute_mceWorkaround = function(mceID) {
	$('[name="' + mceID + '"]').each(function(index, textarea) {
		var already = false;
		$.each(couple.all, function() {
			if(this.$textarea[0] === textarea) {
				already = true;
				return false;
			}
		});
		if(already) {
			return;
		}
		var $textarea = $(textarea);
		var realId = $textarea.attr('data-original-field-id');
		if(realId) {
			new couple($textarea, realId);
		}
	});
};

})(window, jQuery); }
