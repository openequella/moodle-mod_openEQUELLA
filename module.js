/**
 * JavaScript library for EQUELLA module
 */
M.mod_equella = {};

M.mod_equella.submitform = function(Y, formid) {
    var frm = document.getElementById(formid);
    frm.submit();
};

M.mod_equella.display_equella = function(Y, equellaContainer, width, minheight, title, redirecturl) {
    var bodyNode = Y.one('body');
    bodyNode.addClass('equella-page');
    var generate_html = function(append) {
        var iframe = '';
        //if (Y.UA.ie > 0) {
            iframe = '<div class="resourcecontent resourcegeneral"><iframe id="resourceobject" src="'+redirecturl+'"></iframe></div>';
        //} else {
            //var param = '<param name="src" value="'+redirecturl+'" />';
            //iframe = ' <div class="resourcecontent resourcegeneral"><object id="resourceobject" data="'+redirecturl+'" type="text/html">'+param+'</object></div>';
        //}

        var html = Y.Node.create('<div id="'+equellaContainer+'"><div class="yui3-widget-hd">'+title+'</div><div class="yui3-widget-bd">'+iframe+'</div></div>'); 
        var bodyNode = Y.one(document.body);
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
        var obj = Y.one('#'+id);
        if (!obj) {
            return;
        }

        obj.setStyle('width', '0px');
        obj.setStyle('height', '0px');
        var newwidth = get_htmlelement_size(parentContainer, 'width') - 25;

        if (newwidth > 500) {
            obj.setStyle('width', newwidth  + 'px');
        } else {
            obj.setStyle('width', '500px');
        }

        var headerheight = get_htmlelement_size('page-header', 'height');
        var footerheight = get_htmlelement_size('page-footer', 'height');
        var newheight;
        if (initialize) {
            newheight = Y.one('body').get('winHeight') - 100;
            if (newheight < minheight) {
                newheight = minheight;
            }
        } else {
            newheight = get_htmlelement_size(parentContainer, 'height');
        }
        newheight = newheight - 50;
        obj.setStyle('height', newheight+'px');

    };
    Y.use('panel', 'dd-plugin', 'resize-plugin', 'event', function (Y) {
        var body = Y.one('body');
        var bodywidth = body.getStyle('width');
        if (bodywidth == 'auto') {
            bodywidth = body.getComputedStyle('width');
        }
        bodywidth = parseInt(bodywidth);
        var x = (bodywidth - width) / 2;
        var y = 20;

        generate_html(true);
        var panel = new Y.Panel({
            srcNode      : '#' + equellaContainer,
            width        : width,
            zIndex       : 1031,
            xy           : [x, y],
            centered     : false,
            modal        : true,
            visible      : true,
            render       : true,
            //buttons      : [],
            plugins      : [Y.Plugin.Drag, Y.Plugin.Resize]
        });
        panel.show();
        panel.resize.on('resize:resize', function(e) {
            resize_embedded('resourceobject', equellaContainer, false);
        });
        // fix layout if window resized too
        window.onresize = function() {
            resize_embedded('resourceobject', equellaContainer, false);
        };
        resize_embedded('resourceobject', equellaContainer, true);
        var button  = Y.one('#openequellachooser');
        button.on('click', function (e) {
            panel.show();
        });
    });
}

