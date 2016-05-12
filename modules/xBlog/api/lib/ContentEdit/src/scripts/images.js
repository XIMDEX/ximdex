// Generated by CoffeeScript 1.10.0
var extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

ContentEdit.Image = (function(superClass) {
  extend(Image, superClass);

  function Image(attributes, a) {
    var size;
    Image.__super__.constructor.call(this, 'img', attributes);
    this.a = a ? a : null;
    size = this.size();
    this._aspectRatio = size[1] / size[0];
  }

  Image.prototype.cssTypeName = function() {
    return 'image';
  };

  Image.prototype.type = function() {
    return 'Image';
  };

  Image.prototype.typeName = function() {
    return 'Image';
  };

  Image.prototype.createDraggingDOMElement = function() {
    var helper;
    if (!this.isMounted()) {
      return;
    }
    helper = Image.__super__.createDraggingDOMElement.call(this);
    helper.style.backgroundImage = "url(" + this._attributes['src'] + ")";
    return helper;
  };

  Image.prototype.html = function(indent) {
    var attributes, img;
    if (indent == null) {
      indent = '';
    }
    img = indent + "<img" + (this._attributesToString()) + " />";
    if (this.a) {
      attributes = ContentEdit.attributesToString(this.a);
      attributes = attributes + " data-ce-tag=\"img\"";
      return (indent + "<a " + attributes + ">\n") + ("" + ContentEdit.INDENT + img + "\n") + (indent + "</a>");
    } else {
      return img;
    }
  };

  Image.prototype.mount = function() {
    var classes, style;
    this._domElement = document.createElement('div');
    classes = '';
    if (this.a && this.a['class']) {
      classes += ' ' + this.a['class'];
    }
    if (this._attributes['class']) {
      classes += ' ' + this._attributes['class'];
    }
    this._domElement.setAttribute('class', classes);
    style = this._attributes['style'] ? this._attributes['style'] : '';
    style += "background-image:url(" + this._attributes['src'] + ");";
    if (this._attributes['width']) {
      style += "width:" + this._attributes['width'] + "px;";
    }
    if (this._attributes['height']) {
      style += "height:" + this._attributes['height'] + "px;";
    }
    this._domElement.setAttribute('style', style);
    return Image.__super__.mount.call(this);
  };

  Image.droppers = {
    'Image': ContentEdit.Element._dropBoth,
    'PreText': ContentEdit.Element._dropBoth,
    'Static': ContentEdit.Element._dropBoth,
    'Text': ContentEdit.Element._dropBoth
  };

  Image.placements = ['above', 'below', 'left', 'right', 'center'];

  Image.fromDOMElement = function(domElement) {
    var a, attributes, c, childNode, childNodes, i, len;
    a = null;
    if (domElement.tagName.toLowerCase() === 'a') {
      a = this.getDOMElementAttributes(domElement);
      childNodes = (function() {
        var i, len, ref, results;
        ref = domElement.childNodes;
        results = [];
        for (i = 0, len = ref.length; i < len; i++) {
          c = ref[i];
          results.push(c);
        }
        return results;
      })();
      for (i = 0, len = childNodes.length; i < len; i++) {
        childNode = childNodes[i];
        if (childNode.nodeType === 1 && childNode.tagName.toLowerCase() === 'img') {
          domElement = childNode;
          break;
        }
      }
      if (domElement.tagName.toLowerCase() === 'a') {
        domElement = document.createElement('img');
      }
    }
    attributes = this.getDOMElementAttributes(domElement);
    if (attributes['width'] === void 0) {
      if (attributes['height'] === void 0) {
        attributes['width'] = domElement.naturalWidth;
      } else {
        attributes['width'] = domElement.clientWidth;
      }
    }
    if (attributes['height'] === void 0) {
      if (attributes['width'] === void 0) {
        attributes['height'] = domElement.naturalHeight;
      } else {
        attributes['height'] = domElement.clientHeight;
      }
    }
    return new this(attributes, a);
  };

  return Image;

})(ContentEdit.ResizableElement);

ContentEdit.TagNames.get().register(ContentEdit.Image, 'img');