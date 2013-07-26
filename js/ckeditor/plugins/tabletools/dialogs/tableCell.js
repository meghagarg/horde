CKEDITOR.dialog.add("cellProperties",function(v){var u=v.lang.table,t=u.cell,s=v.lang.common,r=CKEDITOR.dialog.validate,q=/^(\d+(?:\.\d+)?)(px|%)$/,p=/^(\d+(?:\.\d+)?)px$/,o=CKEDITOR.tools.bind,n={type:"html",html:"&nbsp;"},m=v.lang.dir=="rtl";function l(b,a){var f=function(){var g=this;d(g);a(g,g._.parentDialog);g._.parentDialog.changeFocus()},e=function(){d(this);this._.parentDialog.changeFocus()},d=function(g){g.removeListener("ok",f);g.removeListener("cancel",e)},c=function(g){g.on("ok",f);g.on("cancel",e)};v.execCommand(b);if(v._.storedDialogs.colordialog){c(v._.storedDialogs.colordialog)}else{CKEDITOR.on("dialogDefinition",function(h){if(h.data.name!=b){return}var g=h.data.definition;h.removeListener();g.onLoad=CKEDITOR.tools.override(g.onLoad,function(i){return function(){c(this);g.onLoad=i;if(typeof i=="function"){i.call(this)}}})})}}return{title:t.title,minWidth:CKEDITOR.env.ie&&CKEDITOR.env.quirks?450:410,minHeight:CKEDITOR.env.ie&&(CKEDITOR.env.ie7Compat||CKEDITOR.env.quirks)?230:220,contents:[{id:"info",label:t.title,accessKey:"I",elements:[{type:"hbox",widths:["40%","5%","40%"],children:[{type:"vbox",padding:0,children:[{type:"hbox",widths:["70%","30%"],children:[{type:"text",id:"width",width:"100px",label:s.width,validate:r.number(t.invalidWidth),onLoad:function(){var b=this.getDialog().getContentElement("info","widthType"),a=b.getElement(),d=this.getInputElement(),c=d.getAttribute("aria-labelledby");d.setAttribute("aria-labelledby",[c,a.$.id].join(" "))},setup:function(b){var a=parseInt(b.getAttribute("width"),10),c=parseInt(b.getStyle("width"),10);!isNaN(a)&&this.setValue(a);!isNaN(c)&&this.setValue(c)},commit:function(b){var a=parseInt(this.getValue(),10),c=this.getDialog().getValueOf("info","widthType");if(!isNaN(a)){b.setStyle("width",a+c)}else{b.removeStyle("width")}b.removeAttribute("width")},"default":""},{type:"select",id:"widthType",label:v.lang.table.widthUnit,labelStyle:"visibility:hidden","default":"px",items:[[u.widthPx,"px"],[u.widthPc,"%"]],setup:function(b){var a=q.exec(b.getStyle("width")||b.getAttribute("width"));if(a){this.setValue(a[2])}}}]},{type:"hbox",widths:["70%","30%"],children:[{type:"text",id:"height",label:s.height,width:"100px","default":"",validate:r.number(t.invalidHeight),onLoad:function(){var b=this.getDialog().getContentElement("info","htmlHeightType"),a=b.getElement(),d=this.getInputElement(),c=d.getAttribute("aria-labelledby");d.setAttribute("aria-labelledby",[c,a.$.id].join(" "))},setup:function(b){var a=parseInt(b.getAttribute("height"),10),c=parseInt(b.getStyle("height"),10);!isNaN(a)&&this.setValue(a);!isNaN(c)&&this.setValue(c)},commit:function(b){var a=parseInt(this.getValue(),10);if(!isNaN(a)){b.setStyle("height",CKEDITOR.tools.cssLength(a))}else{b.removeStyle("height")}b.removeAttribute("height")}},{id:"htmlHeightType",type:"html",html:"<br />"+u.widthPx}]},n,{type:"select",id:"wordWrap",label:t.wordWrap,"default":"yes",items:[[t.yes,"yes"],[t.no,"no"]],setup:function(b){var a=b.getAttribute("noWrap"),c=b.getStyle("white-space");if(c=="nowrap"||a){this.setValue("no")}},commit:function(a){if(this.getValue()=="no"){a.setStyle("white-space","nowrap")}else{a.removeStyle("white-space")}a.removeAttribute("noWrap")}},n,{type:"select",id:"hAlign",label:t.hAlign,"default":"",items:[[s.notSet,""],[s.alignLeft,"left"],[s.alignCenter,"center"],[s.alignRight,"right"]],setup:function(b){var a=b.getAttribute("align"),c=b.getStyle("text-align");this.setValue(c||a||"")},commit:function(b){var a=this.getValue();if(a){b.setStyle("text-align",a)}else{b.removeStyle("text-align")}b.removeAttribute("align")}},{type:"select",id:"vAlign",label:t.vAlign,"default":"",items:[[s.notSet,""],[s.alignTop,"top"],[s.alignMiddle,"middle"],[s.alignBottom,"bottom"],[t.alignBaseline,"baseline"]],setup:function(b){var a=b.getAttribute("vAlign"),c=b.getStyle("vertical-align");switch(c){case"top":case"middle":case"bottom":case"baseline":break;default:c=""}this.setValue(c||a||"")},commit:function(b){var a=this.getValue();if(a){b.setStyle("vertical-align",a)}else{b.removeStyle("vertical-align")}b.removeAttribute("vAlign")}}]},n,{type:"vbox",padding:0,children:[{type:"select",id:"cellType",label:t.cellType,"default":"td",items:[[t.data,"td"],[t.header,"th"]],setup:function(a){this.setValue(a.getName())},commit:function(a){a.renameNode(this.getValue())}},n,{type:"text",id:"rowSpan",label:t.rowSpan,"default":"",validate:r.integer(t.invalidRowSpan),setup:function(b){var a=parseInt(b.getAttribute("rowSpan"),10);if(a&&a!=1){this.setValue(a)}},commit:function(b){var a=parseInt(this.getValue(),10);if(a&&a!=1){b.setAttribute("rowSpan",this.getValue())}else{b.removeAttribute("rowSpan")}}},{type:"text",id:"colSpan",label:t.colSpan,"default":"",validate:r.integer(t.invalidColSpan),setup:function(b){var a=parseInt(b.getAttribute("colSpan"),10);if(a&&a!=1){this.setValue(a)}},commit:function(b){var a=parseInt(this.getValue(),10);if(a&&a!=1){b.setAttribute("colSpan",this.getValue())}else{b.removeAttribute("colSpan")}}},n,{type:"hbox",padding:0,widths:["60%","40%"],children:[{type:"text",id:"bgColor",label:t.bgColor,"default":"",setup:function(b){var a=b.getAttribute("bgColor"),c=b.getStyle("background-color");this.setValue(c||a)},commit:function(b){var a=this.getValue();if(a){b.setStyle("background-color",this.getValue())}else{b.removeStyle("background-color")}b.removeAttribute("bgColor")}},{type:"button",id:"bgColorChoose","class":"colorChooser",label:t.chooseColor,onLoad:function(){this.getElement().getParent().setStyle("vertical-align","bottom")},onClick:function(){var a=this;l("colordialog",function(b){a.getDialog().getContentElement("info","bgColor").setValue(b.getContentElement("picker","selectedColor").getValue())})}}]},n,{type:"hbox",padding:0,widths:["60%","40%"],children:[{type:"text",id:"borderColor",label:t.borderColor,"default":"",setup:function(b){var a=b.getAttribute("borderColor"),c=b.getStyle("border-color");this.setValue(c||a)},commit:function(b){var a=this.getValue();if(a){b.setStyle("border-color",this.getValue())}else{b.removeStyle("border-color")}b.removeAttribute("borderColor")}},{type:"button",id:"borderColorChoose","class":"colorChooser",label:t.chooseColor,style:(m?"margin-right":"margin-left")+": 10px",onLoad:function(){this.getElement().getParent().setStyle("vertical-align","bottom")},onClick:function(){var a=this;l("colordialog",function(b){a.getDialog().getContentElement("info","borderColor").setValue(b.getContentElement("picker","selectedColor").getValue())})}}]}]}]}]}],onShow:function(){var a=this;a.cells=CKEDITOR.plugins.tabletools.getSelectedCells(a._.editor.getSelection());a.setupContent(a.cells[0])},onOk:function(){var c=this;var b=c._.editor.getSelection(),a=b.createBookmarks(),e=c.cells;for(var d=0;d<e.length;d++){c.commitContent(e[d])}c._.editor.forceNextSelectionCheck();b.selectBookmarks(a);c._.editor.selectionChange()}}});