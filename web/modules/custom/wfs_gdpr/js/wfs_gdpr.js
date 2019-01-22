(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.wfs_gdpr_popup = {
    attach: function (context, settings) {
      if (context !== document) {
        return;
      }
      Drupal.wfs_gdpr.execute(context);
    }
  };

  Drupal.wfs_gdpr = {};

  Drupal.wfs_gdpr.execute = function (context) {
    try {
      if (!drupalSettings.wfs_gdpr.popup_enabled) {
        return;
      }
      if (!Drupal.wfs_gdpr.cookiesEnabled()) {
        return;
      }
      //Drupal.wfs_gdpr.updateCheck();
      var status = Drupal.wfs_gdpr.getCurrentStatus();
      var showOnce = drupalSettings.wfs_gdpr.popup_show_once;
      var removeBanner = Drupal.wfs_gdpr.cookieAccepted(
        "cookie_disable_banner"
      );
      // Is show once is enabled then do not use localStorage.
      if (showOnce) {
        removeBanner = true;
        // Set Remove banner to 1 and Remove the local storage if exists.
        try {
          localStorage.removeItem('removeBannerCookie');
        }
        catch (e) {
          // Do nothing.
        }
      }

      if (status === 0 || status === null || !removeBanner) {
        if (!drupalSettings.wfs_gdpr.disagree_do_not_show_popup ||
          status === null
        ) {
          // Disable localStorage when Show once option enabled.
          if (!showOnce) {
            localStorage.setItem("removeBannerCookie", 0);
          }
          Drupal.wfs_gdpr.createPopup(
            context,
            drupalSettings.wfs_gdpr.popup_html_info
          );
          Drupal.wfs_gdpr.attachAgreeEvents();
        }
      }
      else if (
        status === 1 &&
        drupalSettings.wfs_gdpr.popup_agreed_enabled
      ) {
        if (!showOnce) {
          localStorage.setItem("removeBannerCookie", 0);
        }
        Drupal.wfs_gdpr.createPopup(
          context,
          drupalSettings.wfs_gdpr.popup_html_agreed
        );
      }
    } catch (e) { }
  };

  /** Function to create popup markup **/
  Drupal.wfs_gdpr.createPopup = function (context, html) {
    var $popup = $('<div></div>').html(html);
    $popup.attr('id', 'sliding-popup');
    $popup.hide();
    $popup.appendTo('body');
    var height = $popup.height();
    $popup
      .show()
      .attr('class', 'sliding-popup-bottom')
      .css('bottom', -1 * height);
    Drupal.behaviors.verticalTabs.attach(context);
  };

  Drupal.wfs_gdpr.attachAgreeEvents = function () {
    var clickingConfirms =
      drupalSettings.wfs_gdpr.popup_clicking_confirmation;
    var scrollConfirms =
      drupalSettings.wfs_gdpr.popup_scrolling_confirmation;
    var showOnce = drupalSettings.wfs_gdpr.popup_show_once;

    $('.open-settings').click(function (e) {
      e.preventDefault();
      $('.cookie-policy-form').fadeIn('slow');
    });

    $('.cookie-button.close').click(function () {
      $('.cookie-policy-form').fadeOut('slow');
    });
    //  Click on Cookie link when Show once is enabled.
    if (showOnce) {
      $('.cookie-policy-form .vertical-tabs__menu-item.last a').click(function () {
        window.location = $('#wfs-cookie-link a').attr('href');
      });
    }
    // Clone and remove tabs when Show once is disabled.
    else {
      $(".cookie-policy-form .vertical-tabs__menu-item.last a").attr(
        "href",
        $("#wfs-cookie-link a").attr("href")
      );
      var clone = $(".cookie-policy-form .vertical-tabs__menu-item.last");
      $(".cookie-policy-form .vertical-tabs__menu-item.last").remove();
      $(".cookie-policy-form .vertical-tabs__menu").append(clone);
    }

    $('.cookie-policy-form [type="checkbox"]').click(function () {
      var one = false;
      $('.cookie-policy-form [type="checkbox"]').each(function (index, item) {
        if (!$(item).is(':checked')) {
          one = true;
        }
      });

      if (one) {
        $('#accept-all').show();
      } else {
        $('#accept-all').hide();
      }
    });

    var submitActor = null;

    var $submitActors = $('#wfs-gdpr-policy-form').find('input[type=submit]');

    $submitActors.click(function (e) {
      submitActor = this;
    });

    $('#wfs-gdpr-policy-form').submit(function (e) {
      e.preventDefault();
      Drupal.wfs_gdpr.acceptAction();

      if (null === submitActor) {
        submitActor = $submitActors[0];
      }

      var data = $(this).serializeArray(),
        url = $(this).attr('action');

      data.push({
        name: submitActor.name,
        value: submitActor.value
      });
      // Use LocalStorage data when Show once option is disabled.
      if (!showOnce) {
        var removeBanner = Drupal.wfs_gdpr.cookieAccepted(
          "cookie_disable_banner"
        );
        var removeBannerCookie = localStorage.getItem("removeBannerCookie");
        $.each(data, function (k, v) {
          if (v.name == "cookie_disable_banner") {
            data[k]["value"] = removeBannerCookie;
            if (removeBanner && removeBannerCookie == 1) {
              data[k]["value"] = 1;
            }
          }
        });
      }
      $.post(url, data, function () { });
    });

    // Process click button.
    $('.agree-button').click(function (e) {
      // If Show once Disabled then set removeBannerCookie to 1.
      if (!showOnce) {
        localStorage.setItem("removeBannerCookie", 1);
      }
      $('#wfs-gdpr-policy-form').trigger('submit');
    });

    // Set banner police form accept when showOnce disabled.
    if (!showOnce) {
      $("#wfs-gdpr-policy-form #accept").bind("click", function (e) {
        e.preventDefault();
        localStorage.setItem("removeBannerCookie", 1);
        $("#wfs-gdpr-policy-form").trigger("submit");
      });
    }

    if (clickingConfirms) {
      $('a:not(.open-settings), input[type=submit], button[type=submit]').bind(
        'click.euCookieCompliance',
        function (e) {
          if (!$(this).closest('.cookie-policy-form').length) {
            // When Show once is disabled,
            // then evaluate and update removeBannerCookie.
            if (!showOnce) {
              var removeBannerCookie = localStorage.getItem("removeBannerCookie");
              if (removeBannerCookie == 0) {
                localStorage.setItem("removeBannerCookie", 0);
              }
            }
            $('#wfs-gdpr-policy-form').trigger('submit');
          }
        });
    }

    $('.find-more-button')
      .not('.find-more-button-processed')
      .addClass('find-more-button-processed')
      .click(Drupal.wfs_gdpr.moreInfoAction);
  };

  Drupal.wfs_gdpr.attachHideEvents = function () {
    var clickingConfirms =
      drupalSettings.wfs_gdpr.popup_clicking_confirmation;
    $('.hide-popup-button').click(function () {
      Drupal.wfs_gdpr.changeStatus(2);
    });

    if (clickingConfirms) {
      $('a, input[type=submit], button[type=submit]').unbind(
        'click.euCookieCompliance'
      );
    }

    $('.find-more-button')
      .not('.find-more-button-processed')
      .addClass('find-more-button-processed')
      .click(Drupal.wfs_gdpr.moreInfoAction);
  };

  Drupal.wfs_gdpr.acceptAction = function () {
    var agreedEnabled = drupalSettings.wfs_gdpr.popup_agreed_enabled;
    var nextStatus = 1;
    if (!agreedEnabled) {
      Drupal.wfs_gdpr.setStatus(1);
      nextStatus = 2;
    }
    Drupal.wfs_gdpr.changeStatus(nextStatus);
  };

  Drupal.wfs_gdpr.moreInfoAction = function () {
    var showOnce = drupalSettings.wfs_gdpr.popup_show_once;
    if (drupalSettings.wfs_gdpr.disagree_do_not_show_popup) {
      Drupal.wfs_gdpr.setStatus(0);

      // Remove banner when show once is enabled.
      // Or get value from function when is disabled.
      var removeBanner = true;
      if (!showOnce) {
        removeBanner = Drupal.wfs_gdpr.cookieAccepted(
          "cookie_disable_banner"
        );
      }
      if (removeBanner) {
        $("#sliding-popup").remove();
      }
    }
    else {
      if (drupalSettings.wfs_gdpr.popup_link_new_window) {
        window.open(drupalSettings.wfs_gdpr.popup_link);
      }
      else {
        window.location.href = drupalSettings.wfs_gdpr.popup_link;
      }
    }
  };

  Drupal.wfs_gdpr.getCurrentStatus = function () {
    var value = $.cookie('cookie-agreed');
    value = parseInt(value);
    if (isNaN(value)) {
      value = null;
    }
    return value;
  };

  Drupal.wfs_gdpr.changeStatus = function (value) {
    var status = Drupal.wfs_gdpr.getCurrentStatus();
    var showOnce = drupalSettings.wfs_gdpr.popup_show_once;
    var removeBanner = true;
    if (status === value) {
      return;
    }
    if (drupalSettings.wfs_gdpr.popup_position) {
      $('.sliding-popup-top').animate(
        { top: $('#sliding-popup').height() * -1 },
        drupalSettings.wfs_gdpr.popup_delay,
        function () {
          if (!showOnce) {
            removeBanner = Drupal.wfs_gdpr.cookieAccepted(
              "cookie_disable_banner"
            );
          }
          if (status === null) {
            $('#sliding-popup')
              .html(drupalSettings.wfs_gdpr.popup_html_agreed)
              .animate(
                { top: 0 },
                drupalSettings.wfs_gdpr.popup_delay
              );
            Drupal.wfs_gdpr.attachHideEvents();
          }
          else if (status === 1 && removeBanner) {
            $('#sliding-popup').remove();
          }
        });
    }
    else {
      $('.sliding-popup-bottom').animate(
        { bottom: $('#sliding-popup').height() * -1 },
        drupalSettings.wfs_gdpr.popup_delay, function () {
          if (!showOnce) {
            removeBanner = localStorage.getItem("removeBannerCookie");
          }
          if (status === null) {
            $('#sliding-popup')
              .html(drupalSettings.wfs_gdpr.popup_html_agreed)
              .animate(
                { bottom: 0 },
                drupalSettings.wfs_gdpr.popup_delay);
            Drupal.wfs_gdpr.attachHideEvents();
          }
          else if (status === 1 && removeBanner) {
            $('#sliding-popup').remove();
          }
        });
    }
    Drupal.wfs_gdpr.setStatus(value);
  };

  Drupal.wfs_gdpr.setStatus = function (status) {
    var date = new Date();
    var domain = drupalSettings.wfs_gdpr.domain
      ? drupalSettings.wfs_gdpr.domain
      : '';
    var path = drupalSettings.path.baseUrl;
    if (path.length > 1) {
      var pathEnd = path.length - 1;
      if (path.lastIndexOf('/') === pathEnd) {
        path = path.substring(0, pathEnd);
      }
    }
    date.setDate(
      date.getDate() +
      parseInt(drupalSettings.wfs_gdpr.cookie_lifetime)
    );
    $.cookie('cookie-agreed', status, {
      expires: date,
      path: path,
      domain: domain
    });
  };

  Drupal.wfs_gdpr.hasAgreed = function () {
    var status = Drupal.wfs_gdpr.getCurrentStatus();
    return (status === 1 || status === 2);
  };

  Drupal.wfs_gdpr.showBanner = function () {
    var showBanner = false;
    var status = Drupal.wfs_gdpr.getCurrentStatus();
    if (status === 0 || status === null) {
      if (!drupalSettings.wfs_gdpr.disagree_do_not_show_popup ||
        status === null
      ) {
        showBanner = true;
      }
    }
    else if (
      status === 1 && drupalSettings.wfs_gdpr.popup_agreed_enabled
    ) {
      showBanner = true;
    }
    return showBanner;
  };

  Drupal.wfs_gdpr.cookiesEnabled = function () {
    var cookieEnabled = navigator.cookieEnabled;
    if (typeof navigator.cookieEnabled === 'undefined' && !cookieEnabled) {
      document.cookie = 'testCookie';
      cookieEnabled = (document.cookie.indexOf('testCookie') !== -1);
    }
    return cookieEnabled;
  };

  Drupal.wfs_gdpr.reloadPage = function () {
    if (drupalSettings.wfs_gdpr.reload_page) {
      location.reload();
    }
  };

  // This code upgrades the cookie agreed
  // status when upgrading for an old version.
  Drupal.wfs_gdpr.updateCheck = function () {
    var legacyCookie = 'cookie-agreed-' +
      drupalSettings.wfs_gdpr.popup_language;
    var domain = drupalSettings.wfs_gdpr.domain
      ? drupalSettings.wfs_gdpr.domain
      : '';
    var path = drupalSettings.path.baseUrl;
    var cookie;
    if ((cookie = $.cookie(legacyCookie)) !== null) {
      $.cookie('cookie-agreed', cookie, {
        path: path,
        domain: domain
      });
      $.cookie(legacyCookie, null, {
        path: path,
        domain: domain
      });
    }
  };

  // Get cookies accepted by the current user (IP).
  Drupal.wfs_gdpr.cookieAccepted = function (cookieType) {
    // Get Cookies accepted.
    var cookiesAccepted = drupalSettings.wfs_gdpr.cookies_accepted
      ? drupalSettings.wfs_gdpr.cookies_accepted
      : null;
    if (cookiesAccepted && cookiesAccepted.length) {
      return true;
    }
    return false;
  };

})(jQuery, Drupal, drupalSettings);
