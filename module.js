/**
 * JavaScript library for EQUELLA module
 */
M.mod_equella = {};

M.mod_equella.submitform = function(Y, formid) {
    var frm = document.getElementById(formid);
    frm.submit();
};

M.mod_equella.display_equella = function(Y, equellaContainer, minwidth, minheight,
	title, redirecturl) {
    var bodyNode = Y.one('body');
    var iframeid = 'resourceobject';
    var initialheight = Y.one('body').get('winHeight') * 0.9;
    var initialwidth = Y.one('body').get('winWidth') * 0.8;
    bodyNode.addClass('equella-page');

    var generate_html = function(append) {
	var iframe = '<div class="resourcecontent resourcegeneral"><iframe id="'+iframeid+'"></iframe></div>';

	var html = Y.Node.create('<div id="' + equellaContainer
		+ '"><div class="yui3-widget-hd">' + title
		+ '</div><div class="yui3-widget-bd">' + iframe
		+ '</div></div>');
	if (append) {
	    bodyNode.append(html);
	}
	return html;
    }
    var get_htmlelement_size = function(el, prop) {
	if (Y.Lang.isString(el)) {
	    el = Y.one('#' + el);
	}
	// Ensure element exists.
	if (el) {
	    var val = el.getStyle(prop);
	    if (val == 'auto') {
		val = el.getComputedStyle(prop);
	    }
	    return parseInt(val);
	} else {
	    return 0;
	}
    };

    var resize_embedded = function(id, parentContainer, initialize) {
	var obj = Y.one('#' + id);
	if (!obj) {
	    return;
	}

	obj.setStyle('width', '0px');
	obj.setStyle('height', '0px');
	var newwidth = get_htmlelement_size(parentContainer, 'width') - 25;
	

	if (newwidth > 500) {
	    obj.setStyle('width', newwidth + 'px');
	} else {
	    obj.setStyle('width', '500px');
	}

	var headerheight = get_htmlelement_size('page-header', 'height');
	var footerheight = get_htmlelement_size('page-footer', 'height');
	var newheight;
	var newwidth;
	if (initialize) {
            if (initialheight < minheight) {
                newheight = minheight;
            } else {
                newheight = initialheight;
            }	
            if (initialwidth < minwidth) {
                newwidth = minwidth;
            } else {
                newwidth = initialwidth;
            }
	} else {
		newheight = get_htmlelement_size(parentContainer, 'height');
		newwidth = get_htmlelement_size(parentContainer, 'width');
	}
        newheight = newheight - 50;
	obj.setStyle('height', newheight + 'px');
	if (initialize) {
            obj.setAttribute('src', redirecturl);
        }
    };
    Y.use('panel', 'dd-plugin', 'resize-plugin', 'event', function(Y) {
	var body = Y.one('body');
	var bodywidth = body.getStyle('width');
	if (bodywidth == 'auto') {
	    bodywidth = body.getComputedStyle('width');
	}
	bodywidth = parseInt(bodywidth);
	var x = (bodywidth - minwidth) / 2;
	var y = 20;

	generate_html(true);
	var panel = new Y.Panel({
	    srcNode : '#' + equellaContainer,
	    width : initialwidth,
	    height : initialheight,
	    zIndex : 4031,
	    xy : [ x, y ],
	    centered : true,
	    modal : true,
	    visible : true,
	    render : true,
	    //buttons      : [],
	    plugins : [ Y.Plugin.Drag, Y.Plugin.Resize ]
	});
	panel.show();
	panel.resize.on('resize:resize', function(e) {
	    resize_embedded(iframeid, equellaContainer, false);
	});
	// fix layout if window resized too
	window.onresize = function() {
	    resize_embedded(iframeid, equellaContainer, false);
	};
	resize_embedded(iframeid, equellaContainer, true);
	var button = Y.one('#openequellachooser');
	button.on('click', function(e) {
	    panel.show();
	});
    });
}
