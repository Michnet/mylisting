!(function (e) {
    "function" == typeof define && define.amd ? define("addListing", e) : e();
  })(function () {
    "use strict";
    
    let fields = document.querySelectorAll('.form-section-wrapper');
    if(fields){
    fields.forEach((fil) => {
        let buttonEl = fil.querySelector('.pf-head');
        if(buttonEl){
            buttonEl.addEventListener("click", ()=>{
                let active = document.querySelector('.open_field');
                if(active){
                    active.classList.remove('open_field');
                }
            fil.classList.toggle("open_field");
            });
        }
    })
    }

    jQuery(function (t) {
      t(
        ".file-upload-field.multiple-uploads .job-manager-uploaded-files"
      ).sortable({ helper: "clone", appendTo: document.body }),
        t('.c27-work-hours .day-wrapper .work-hours-type input[type="radio"]').on(
          "change",
          function (e) {
            t(this).val();
            t(this)
              .parents(".day-wrapper")
              .removeClass(
                [
                  "day-status-enter-hours",
                  "day-status-closed-all-day",
                  "day-status-open-all-day",
                  "day-status-by-appointment-only",
                ].join(" ")
              )
              .addClass("day-status-" + t(this).val());
          }
        );
    }),

      /* jQuery(function (a) {
        var d,
          o,
          e,
          t,
          i,
          n = {};
        (e = a("#submit-job-form .form-section-wrapper")).length <= 1 ||
          ((d = (70 * window.innerHeight) / 100),
          (o = (5 * window.innerHeight) / 100),
          a(window)
            .on(
              "scroll",
              MyListing.Helpers.debounce(function () {
                var i = [],
                  t =
                    (e.each(function (e, t) {
                      var n = t.getBoundingClientRect(),
                        a = d - n.top,
                        n = o - n.top;
                      0 <= a && i.push({ el: t, diff: a, max_diff: n });
                    }),
                    e.removeClass("active"),
                    n.Nav.clearAll(),
                    !1);
                i.reverse().forEach(function (e) {
                  t
                    ? e.max_diff <= 0 &&
                      (e.el.classList.add("active"), n.Nav.highlight(e.el.id))
                    : (e.el.classList.add("active"),
                      (t = !0),
                      n.Nav.highlight(e.el.id));
                });
              }, 20)
            )
            .scroll()),
          (t = a(
            "#submit-job-form .form-section-wrapper:not(#form-section-submit)"
          )),
          (i = a(".add-listing-nav")).length &&
            ((n.Nav = {
              clearAll: function () {
                i.find("li").removeClass("active");
              },
              highlight: function (e) {
                e = i.find("#" + e + "-nav");
                e.length && e.addClass("active");
              },
            }),
            t.length <= 1
              ? i.hide()
              : t.each(function (e, t) {
                  var n = a(this).find(".pf-head h5").html();
                  "string" == typeof n &&
                    ((n = a(
                      '<li id="' +
                        a(this).attr("id") +
                        '-nav"><a href="#"><i><span></span></i>' +
                        n +
                        "</a></li>"
                    )).click(function (e) {
                      e.preventDefault(),
                        a("html, body").animate({
                          scrollTop:
                            a(t).offset().top -
                            (5 * window.innerHeight) / 100 -
                            90,
                        });
                    }),
                    i.find("ul").append(n));
                }));
      }), */
      jQuery(function (y) {
        y(".event-picker").each(function () {
          var t = y(this),
            e = t.data("dates"),
            d = t.data("key"),
            n = t.data("limit"),
            o = "no" !== t.data("timepicker"),
            v = t.data("l10n"),
            s = t.find(".dates-list"),
            a = t.find(".date-add-new"),
            i = e.length + 1,
            r = t.find(".datetpl").text();
          function l() {
            var e = t.find(".single-date").length;
            n <= e ? a.hide() : a.show(), e < 1 && c();
          }
          function c() {
            u({
              start: "",
              end: "",
              repeat: !1,
              frequency: 2,
              unit: "weeks",
              until: moment().add(1, "years").locale("en").format("YYYY-MM-DD"),
              index: i++,
            });
          }
          function u(e) {
            var t = y(r.replace(/{date}/g, d + "[" + e.index + "]")),
              c = t.find(".is-recurring input"),
              u = t.find(".date-start input"),
              p = t.find(".date-end input"),
              f = t.find(".repeat-frequency input"),
              m = t.find(".repeat-unit"),
              g = t.find(".repeat-message"),
              h = t.find(".repeat-end input");
            function n() {
              if (c.prop("checked")) {
                var e = u.val(),
                  t = p.val(),
                  n = h.val(),
                  a = parseInt(f.val(), 10),
                  i = m.find("input:checked").val();
                if (e.length && t.length && n.length && a) {
                  (e = moment(e)),
                    (t = moment(t)),
                    (n = moment(n)).set({ hour: 23, minute: 59, second: 59 }),
                    "weeks" === i && ((i = "days"), (a *= 7)),
                    "years" === i && ((i = "months"), (a *= 12));
                  for (
                    var n = Math.abs(e.diff(n, i)),
                      d = Math.floor(n / a),
                      o = [],
                      s = 1;
                    s < Math.min(d + 1, 6);
                    s++
                  ) {
                    var r = e.clone().add(a * s, i),
                      l = t.clone().add(a * s, i);
                    o.push(
                      ""
                        .concat(r.format(CASE27.l10n.datepicker.format), " - ")
                        .concat(l.format(CASE27.l10n.datepicker.format))
                    );
                  }
                  n = v.next_five.replace("%d", d);
                  d < 1
                    ? (n = v.no_recurrences)
                    : d < 5 && (n = v.next_recurrences),
                    g
                      .show()
                      .html(
                        "<span>"
                          .concat(n, "</span><ul><li>")
                          .concat(o.join("</li><li>"), "</li></ul>")
                      );
                } else g.hide();
              }
            }
            u.val(e.start),
              p.val(e.end),
              c.prop("checked", e.repeat),
              f.val(e.frequency),
              m.find('input[value="'.concat(e.unit, '"]')).prop("checked", !0),
              h.val(e.until),
              e.repeat && t.find(".recurrence").addClass("is-open"),
              c.on("change", function () {
                n(),
                  y(this).prop("checked")
                    ? t.find(".recurrence").addClass("is-open")
                    : t.find(".recurrence").removeClass("is-open");
              });
            new MyListing.Datepicker(u, { timepicker: o });
            var a = new MyListing.Datepicker(p, { timepicker: o }),
              i = new MyListing.Datepicker(h);
            e.start && t.find(".date-start").removeClass("date-empty"),
              e.end && t.find(".date-end").removeClass("date-empty"),
              u.on("datepicker:change", function (e) {
                a.setMinDate(moment(e.detail.value)),
                  i.setMinDate(moment(e.detail.value)),
                  n(),
                  e.detail.value
                    ? t.find(".date-start").removeClass("date-empty")
                    : t.find(".date-start").addClass("date-empty");
              }),
              p.on("datepicker:change", function (e) {
                n(),
                  e.detail.value
                    ? t.find(".date-end").removeClass("date-empty")
                    : t.find(".date-end").addClass("date-empty");
              }),
              h.on("datepicker:change", n),
              f.on("input", n),
              m.find("input").on("change", n),
              n(),
              s.append(t);
          }
          e.forEach(function (e, t) {
            u({
              start: e.start,
              end: e.end,
              repeat: e.repeat,
              frequency: e.repeat ? e.frequency : 2,
              unit: e.repeat ? e.unit : "weeks",
              until: e.repeat
                ? e.until
                : moment(e.start)
                    .add(1, "years")
                    .locale("en")
                    .format("YYYY-MM-DD"),
              index: t,
            });
          }),
            e.length || c(),
            a.click(function (e) {
              e.preventDefault(),
                u({
                  start: "",
                  end: "",
                  repeat: !1,
                  frequency: 2,
                  unit: "weeks",
                  until: moment()
                    .add(1, "years")
                    .locale("en")
                    .format("YYYY-MM-DD"),
                  index: i++,
                }),
                l();
            }),
            y(this).on("click", ".remove-date", function (e) {
              e.preventDefault(), y(this).parents(".single-date").remove(), l();
            }),
            l();
        });
      }),
      jQuery(function (g) {
        MyListing.Maps &&
          MyListing.Maps.loaded &&
          (g(".repeater-custom").each(function (e, t) {
            var f = parseInt(g(t).data("max"), 10),
              m = g(t).find(".add-location");
            g(t)
              .repeater({
                initEmpty: !0,
                ready: function (e) {},
                hide: function (e) {
                  var n = g(this).find(".location-field-wrapper").data("index");
                  MyListing.Maps.instances;
                  MyListing.Maps.instances.forEach(function (e, t) {
                    e.id == n && delete MyListing.Maps.instances[t];
                  }),
                    (MyListing.Maps.instances = MyListing.Maps.instances.filter(
                      function (e) {
                        return null !== e;
                      }
                    )),
                    e(),
                    g("div[data-repeater-item] > .location-field-wrapper")
                      .length >= f
                      ? m.hide()
                      : m.show();
                },
                show: function () {
                  var e = this,
                    t =
                      (g(e).show(),
                      g("div[data-repeater-item] > .location-field-wrapper")),
                    n = g(e).find(".delete-repeater-item"),
                    n =
                      (t.length >= f ? m.hide() : m.show(),
                      1 == f ? n.hide() : n.show(),
                      t.eq(-2).length
                        ? ((n = t.eq(-2).data("index")),
                          g(e)
                            .find(".location-field-wrapper")
                            .attr("data-index", n + 1),
                          g(e)
                            .find(".location-picker-custom-map")
                            .attr("id", n + 1))
                        : (g(e)
                            .find(".location-field-wrapper")
                            .attr("data-index", t.length - 1),
                          g(e)
                            .find(".location-picker-custom-map")
                            .attr("id", t.length - 1)),
                      new MyListing.Maps.Map(g(e).find(".c27-custom-map").get(0)),
                      new MyListing.Maps.Autocomplete(
                        g(e).find(".address-field").get(0)
                      ),
                      g(e).find(".location-field-wrapper")),
                    t = g(e).find(".location-picker-custom-map").attr("id"),
                    a = MyListing.Maps.getInstance(t).instance,
                    i =
                      (g(e)
                        .find(".cts-custom-get-location")
                        .on("click", function (e) {
                          e.preventDefault();
                          var t = jQuery(jQuery(this).parents(".repeater-item"));
                          t.find(".cts-custom-get-location").length &&
                            (a && MyListing.Geocoder.setMap(a.instance),
                            MyListing.Geocoder.getUserLocation({
                              receivedAddress: function (e) {
                                if (
                                  (t.find(".address-field").val(e.address),
                                  t.find(".address-field").data("autocomplete"))
                                )
                                  return t
                                    .find(".address-field")
                                    .data("autocomplete")
                                    .fireChangeEvent(e);
                              },
                            }));
                        }),
                      n.data("options")),
                    d = n.find(".location-coords"),
                    o = n.find(".latitude-input"),
                    s = n.find(".longitude-input"),
                    r = n.find(".address-field"),
                    l = n.find('.lock-pin input[type="checkbox"]'),
                    t = n.find(".enter-coordinates-toggle > span"),
                    c = new MyListing.Maps.Marker({
                      position: p(),
                      map: a,
                      template: { type: "traditional" },
                    });
                  function u() {
                    var e = p();
                    c.setPosition(e),
                      a.panTo(e),
                      "" !== o.val().trim() &&
                        "" !== s.val().trim() &&
                        (o.val(e.getLatitude()), s.val(e.getLongitude()));
                  }
                  function p() {
                    return o.val().trim() && s.val().trim()
                      ? new MyListing.Maps.LatLng(o.val(), s.val())
                      : new MyListing.Maps.LatLng(
                          i["default-lat"],
                          i["default-lng"]
                        );
                  }
                  a.addListener("click", function (e) {
                    l.prop("checked") ||
                      ((e = a.getClickPosition(e)),
                      c.setPosition(e),
                      o.val(e.getLatitude()),
                      s.val(e.getLongitude()),
                      MyListing.Geocoder.geocode(
                        e.toGeocoderFormat(),
                        function (e) {
                          e && r.val(e.address);
                        }
                      ));
                  }),
                    r.on("autocomplete:change", function (e) {
                      var t;
                      !l.prop("checked") &&
                        e.detail.place &&
                        e.detail.place.latitude &&
                        e.detail.place.longitude &&
                        ((t = new MyListing.Maps.LatLng(
                          e.detail.place.latitude,
                          e.detail.place.longitude
                        )),
                        c.setPosition(t),
                        o.val(e.detail.place.latitude),
                        s.val(e.detail.place.longitude),
                        a.panTo(t));
                    }),
                    a.addListenerOnce("idle", function (e) {
                      a.setZoom(i["default-zoom"]);
                    }),
                    l
                      .on("change", function (e) {
                        a.trigger("resize"), a.setCenter(p());
                      })
                      .change(),
                    t.click(function (e) {
                      d.toggleClass("hide");
                    }),
                    o.blur(u),
                    s.blur(u);
                },
              })
              .setList(g(t).data("list"));
          }),
          jQuery(".field-type-location .address-field").each(function (e, t) {
            new MyListing.Maps.Autocomplete(t);
          }),
          jQuery(".cts-custom-get-location").each(function (e, t) {
            jQuery(t).on("click", function (e) {
              e.preventDefault();
              var t = jQuery(jQuery(this).parent(".repeater-item"));
              t.find(".cts-custom-get-location").length &&
                ((e = MyListing.Maps.getInstance(jQuery(this))) &&
                  MyListing.Geocoder.setMap(e.instance),
                MyListing.Geocoder.getUserLocation({
                  receivedAddress: function (e) {
                    if (
                      (t.find(".cts-custom-get-location").val(e.address),
                      t.find(".cts-custom-get-location").data("autocomplete"))
                    )
                      return t
                        .find(".cts-custom-get-location")
                        .data("autocomplete")
                        .fireChangeEvent(e);
                  },
                }));
            });
          }));
      }),
      jQuery(function (t) {
        t("body").hasClass("add-listing-form") &&
          (document.addEventListener(
            "invalid",
            function (e) {
              jQuery(e.target).addClass("invalid"),
                jQuery("html, body").animate(
                  { scrollTop: jQuery(jQuery(".invalid")[0]).offset().top - 150 },
                  0
                );
            },
            !0
          ),
          document.addEventListener(
            "change",
            function (e) {
              jQuery(e.target).removeClass("invalid");
            },
            !0
          )),
          t(".file-upload-field").on(
            "click touchstart",
            ".job-manager-remove-uploaded-file",
            function () {
              return t(this).closest(".job-manager-uploaded-file").remove(), !1;
            }
          ),
          t("#submit-job-form").on("submit", function (e) {
            t(".add-listing-loader").show().removeClass("loader-hidden");
          });
      });
  });
  