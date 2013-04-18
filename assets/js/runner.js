var App, Snippets, CodeSnippet, snippet_id;
$(function(){

  /**
   * Configures backbone data sync
   * 
   * @param  {String}         method  HTTP Method
   * @param  {Backbone.Model} model   Backbone model instance
   * @param  {Object}         options REquest options, callbacks etc.
   */
  Backbone.sync = function(method, model, options) {
    /**
     * Set up method to use in HTTP Headers
     * @type {String}
     */
    // $.ajaxSetup({ type: method });
    /**
     * Make an ajax request
     */
    var default_data = {};

    if(method == 'create' || method == 'update') {
      $.extend(default_data, model.attributes);
      options.type = 'POST';
    }
    options.data = $.extend(default_data, options.data);

    var url = typeof(model.url) == 'function' ? model.url() : model.url;

    // Append method:
    url += '/' + method;

    $.ajax(url, options);
  };

  /**
   * CodeSnippet Model
   * 
   * @type {Backbone.Model}
   */
  var CodeSnippet = Backbone.Model.extend({
    urlRoot: './index.php/snippets',

    /**
     * Defaults
     * @return {Object}
     */
    defaults: function() {
      return {
        id: null,
        content: "<?php // write some code\n\n\n\r?>"
      }
    },
  });
  /**
   * Snippets collection
   * 
   * @type {Backbone.Collection}
   */
  var SnippetsList = Backbone.Collection.extend({
    /**
     * Collection entities model
     * @type {Backbone.Model}
     */
    model: CodeSnippet,
    url: './index.php/snippets',
    parse: function(response, xhr) {
      return response;
    }
  });

  Snippets = new SnippetsList;

  var SnippetListView = Backbone.View.extend({
    tagName: "select",

    /**
     * 
     * @type {Object}
     */
    attributes: {
      name: "snippet"
    },

    /**
     * Events
     * 
     * @type {Object}
     */
    events: {
      'change': "_on_snippet_changed"
    },

    initialize: function() {
      this.collection.bind("reset", this.render, this);
      this.collection.bind("add", this.render, this);
    },

    _on_snippet_changed: function(e) {
      var selected = $(e.target).find(':selected');
      this.trigger('snippets:selected', selected.data('id'));
    },

    render: function() {
      $(this.el).html('');
      $(this.el).prepend('<option>Select snippet</option>');
      if(this.collection.length) {
        _.each(this.collection.models, function (snippet) {
          $(this.el).append(new SnippetListOptionView( { model: snippet } ).render().el);
        }, this);
      }
      return this;
    },

    select_snippet: function(snippet_id) {
      $('select option[value="'+snippet_id+'"]').attr('selected', true);
    }
  });

  var SnippetListOptionView = Backbone.View.extend({
    tagName: "option",

    template: _.template($('#snippets-option-template').html()),

    render:function () {
      $(this.el).html(
        this.template(this.model.toJSON())
      );
      $(this.el).attr('data-id', this.model.get('id'));
      $(this.el).attr('value', this.model.get('id'));
      $(this.el).html(this.model.get('id'));
      return this;
    },


  });

  var SnippetView = Backbone.View.extend({
    /**
     * 
     * @type {String}
     */
    tagName: "textarea",
    /**
     * 
     * @type {Object}
     */
    attributes: {
      id: "code",
      rows: 20,
      name: "code"
    },
    /**
     * 
     * @type {function}
     */
    template: _.template($('#code-editor-template').html()),
    /**
     * 
     * @type {Object}
     */
    events: {},
    /**
     * CodeMirror
     * 
     * @type {CodeMirror}
     */
    _editor: null,

    initialize: function() {
      if(!this.model) {
        this.model = new CodeSnippet();
      }

      this.model.bind('change', this.render, this);
    },

    render: function() {
      this.$el.html(
        this.template(
          this.model.toJSON()
        )
      );
      return this;
    }
  });

  var NavigationView = Backbone.View.extend({
    tagName: "div",
    
    className: 'sidebar-nav',

    id: 'sidebar',
    /**
     * 
     * @type {function}
     */
    template: _.template($('#code-editor-navigation-template').html()),

    initialize: function(view_vars) {
      this._view_vars = view_vars;
    },

    render: function() {
      this.$el.html(
        this.template(this._view_vars)
      );
      return this;
    }
  });

  /**
   * 
   * @type {Backbone.View}
   */
  var AppView = Backbone.View.extend({
    _current_snippet: null,
    // App main container
    el: $('#app_container'),
    /**
     * Events
     * @type {Object}
     */
    events: {
      'click #run_code_btn': "run_code",
      'click #save_snippet_btn': "save_snippet",
      'click #delete_snippet': "delete_snippet"
    },

    /**
     * Initialize app
     */
    initialize: function() {
      this._editor = this.$('#code_editor');
      this._navigation = this.$('#app_navigation');
    },

    render: function() {
      this.snippets_view = new SnippetListView({collection : Snippets});
      this.snippets_view.bind('snippets:selected', this.load_snippet, this);
      Snippets.fetch();
      
      // Create app navigation 
      var navigation_view = new NavigationView({});
      this._navigation.html(navigation_view.render().el);
      this._navigation.prepend(this.snippets_view.el);

      this._inintialize_snippet();

      // Create code input area
      var code_input_view = new SnippetView();
      this.$('#code_editor').html(code_input_view.render().el);
      this._initialize_code_mirror_plugin();
    },

    _inintialize_snippet: function() {
      if(!snippet_id) {
        return;
      }

      this.load_snippet(snippet_id);

      this.snippets_view.select_snippet(snippet_id);

      this._navigation.find('#delete_snippet').removeClass('disabled');
    },

    run_code: function(e) {
      var code = this._editor.getValue();
      $.post('run_code', { code: code }, function(response) {
        this.$('#code_output').html(response.result);
        this.$('#code_errors').html(response.errors);
      }.bind(this));
    },

    save_snippet: function() {
      var snippet;
      if(this._current_snippet) {
        snippet = this._current_snippet;
      } 
      else {
        snippet = new CodeSnippet();
      }
      snippet.save({
        content: this._editor.getValue()
      }, 
      { success: function(response) {
          Snippets.fetch();
        }.bind(this)
      });
    },

    delete_snippet: function(snippet) {
      if(!this._current_snippet) {
        return;
      }
      var func = function() {Snippets.fetch(); }.bind(this);
      this._current_snippet.destroy({success: func});
      window.location.replace('./');
    },

    load_snippet: function(snippet) {
      var current_snippet_id = window.location.href.substr(window.location.href.lastIndexOf('/')+1);
      if(current_snippet_id != snippet) {
        window.location.replace(snippet);
      }
      /**
       * Retrieve selected snippet JSON data
       */
      var snippet = new CodeSnippet({id: snippet});
      var self = this;
      snippet.fetch({
        success: function(response) {
          self._editor.setValue(snippet.get("content"));
        }
      });
      this._current_snippet = snippet;
    },

    _initialize_code_mirror_plugin: function() {
      this._editor = CodeMirror.fromTextArea(document.getElementById("code"), {
        lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 2,
        indentWithTabs: false,
        enterMode: "keep",
        tabMode: "shift"
      });
    }

  });
  App = new AppView;

  App.render();
});