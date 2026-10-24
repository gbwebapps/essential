var Doctum = {
    treeJson: {"tree":{"l":0,"n":"","p":"","c":[{"l":1,"n":"[Global Namespace]","p":"[Global_Namespace]"},{"l":1,"n":"App","p":"App","c":[{"l":2,"n":"Cells","p":"App/Cells","c":[{"l":3,"n":"BackendButtonsCell","p":"App/Cells/BackendButtonsCell"}]},{"l":2,"n":"Commands","p":"App/Commands","c":[{"l":3,"n":"CheckE2EDatabase","p":"App/Commands/CheckE2EDatabase"}]},{"l":2,"n":"Controllers","p":"App/Controllers","c":[{"l":3,"n":"Backend","p":"App/Controllers/Backend","c":[{"l":4,"n":"Components","p":"App/Controllers/Backend/Components","c":[{"l":5,"n":"ExportController","p":"App/Controllers/Backend/Components/ExportController"},{"l":5,"n":"GalleryOneController","p":"App/Controllers/Backend/Components/GalleryOneController"},{"l":5,"n":"ImportController","p":"App/Controllers/Backend/Components/ImportController"},{"l":5,"n":"UploadPreviewController","p":"App/Controllers/Backend/Components/UploadPreviewController"}]},{"l":4,"n":"AccountController","p":"App/Controllers/Backend/AccountController"},{"l":4,"n":"AdminsController","p":"App/Controllers/Backend/AdminsController"},{"l":4,"n":"AuditsController","p":"App/Controllers/Backend/AuditsController"},{"l":4,"n":"AuthController","p":"App/Controllers/Backend/AuthController"},{"l":4,"n":"BackendController","p":"App/Controllers/Backend/BackendController"},{"l":4,"n":"DashboardController","p":"App/Controllers/Backend/DashboardController"},{"l":4,"n":"GroupsController","p":"App/Controllers/Backend/GroupsController"},{"l":4,"n":"LogsController","p":"App/Controllers/Backend/LogsController"},{"l":4,"n":"SettingsController","p":"App/Controllers/Backend/SettingsController"},{"l":4,"n":"TokensController","p":"App/Controllers/Backend/TokensController"},{"l":4,"n":"ToolsController","p":"App/Controllers/Backend/ToolsController"}]},{"l":3,"n":"Frontend","p":"App/Controllers/Frontend","c":[{"l":4,"n":"FrontendController","p":"App/Controllers/Frontend/FrontendController"},{"l":4,"n":"HomeController","p":"App/Controllers/Frontend/HomeController"}]},{"l":3,"n":"BaseController","p":"App/Controllers/BaseController"}]},{"l":2,"n":"Filters","p":"App/Filters","c":[{"l":3,"n":"Backend","p":"App/Filters/Backend","c":[{"l":4,"n":"AuthorizationFilter","p":"App/Filters/Backend/AuthorizationFilter"},{"l":4,"n":"GuestFilter","p":"App/Filters/Backend/GuestFilter"},{"l":4,"n":"LanguageFilter","p":"App/Filters/Backend/LanguageFilter"},{"l":4,"n":"PermissionFilter","p":"App/Filters/Backend/PermissionFilter"},{"l":4,"n":"SuperAdminFilter","p":"App/Filters/Backend/SuperAdminFilter"}]}]},{"l":2,"n":"Interfaces","p":"App/Interfaces","c":[{"l":3,"n":"ExportValidationInterface","p":"App/Interfaces/ExportValidationInterface"},{"l":3,"n":"ImportValidationInterface","p":"App/Interfaces/ImportValidationInterface"}]},{"l":2,"n":"Libraries","p":"App/Libraries","c":[{"l":3,"n":"Backend","p":"App/Libraries/Backend","c":[{"l":4,"n":"AdminsClass","p":"App/Libraries/Backend/AdminsClass"},{"l":4,"n":"AuditsClass","p":"App/Libraries/Backend/AuditsClass"},{"l":4,"n":"AuthClass","p":"App/Libraries/Backend/AuthClass"},{"l":4,"n":"AuthorizationClass","p":"App/Libraries/Backend/AuthorizationClass"},{"l":4,"n":"BackendClass","p":"App/Libraries/Backend/BackendClass"},{"l":4,"n":"DashboardClass","p":"App/Libraries/Backend/DashboardClass"},{"l":4,"n":"EmailService","p":"App/Libraries/Backend/EmailService"},{"l":4,"n":"LogsClass","p":"App/Libraries/Backend/LogsClass"},{"l":4,"n":"SettingsClass","p":"App/Libraries/Backend/SettingsClass"},{"l":4,"n":"TokensClass","p":"App/Libraries/Backend/TokensClass"},{"l":4,"n":"ToolsClass","p":"App/Libraries/Backend/ToolsClass"},{"l":4,"n":"UploadClass","p":"App/Libraries/Backend/UploadClass"}]},{"l":3,"n":"Frontend","p":"App/Libraries/Frontend","c":[{"l":4,"n":"FrontendClass","p":"App/Libraries/Frontend/FrontendClass"},{"l":4,"n":"HomeClass","p":"App/Libraries/Frontend/HomeClass"}]},{"l":3,"n":"AppOtpService","p":"App/Libraries/AppOtpService"},{"l":3,"n":"CryptoService","p":"App/Libraries/CryptoService"},{"l":3,"n":"EmailOtpService","p":"App/Libraries/EmailOtpService"},{"l":3,"n":"ImageFileSystemService","p":"App/Libraries/ImageFileSystemService"},{"l":3,"n":"RegExp","p":"App/Libraries/RegExp"},{"l":3,"n":"Token","p":"App/Libraries/Token"}]},{"l":2,"n":"Models","p":"App/Models","c":[{"l":3,"n":"Backend","p":"App/Models/Backend","c":[{"l":4,"n":"Components","p":"App/Models/Backend/Components","c":[{"l":5,"n":"ExportModel","p":"App/Models/Backend/Components/ExportModel"},{"l":5,"n":"GalleryOneModel","p":"App/Models/Backend/Components/GalleryOneModel"},{"l":5,"n":"ImportModel","p":"App/Models/Backend/Components/ImportModel"},{"l":5,"n":"UploadPreviewModel","p":"App/Models/Backend/Components/UploadPreviewModel"}]},{"l":4,"n":"AccountModel","p":"App/Models/Backend/AccountModel"},{"l":4,"n":"AdminsModel","p":"App/Models/Backend/AdminsModel"},{"l":4,"n":"AuditsModel","p":"App/Models/Backend/AuditsModel"},{"l":4,"n":"AuthModel","p":"App/Models/Backend/AuthModel"},{"l":4,"n":"BackendModel","p":"App/Models/Backend/BackendModel"},{"l":4,"n":"DashboardModel","p":"App/Models/Backend/DashboardModel"},{"l":4,"n":"GroupsModel","p":"App/Models/Backend/GroupsModel"},{"l":4,"n":"LogsModel","p":"App/Models/Backend/LogsModel"},{"l":4,"n":"MenuCountsModel","p":"App/Models/Backend/MenuCountsModel"},{"l":4,"n":"SettingsModel","p":"App/Models/Backend/SettingsModel"},{"l":4,"n":"TokensModel","p":"App/Models/Backend/TokensModel"},{"l":4,"n":"ToolsModel","p":"App/Models/Backend/ToolsModel"}]},{"l":3,"n":"Frontend","p":"App/Models/Frontend","c":[{"l":4,"n":"FrontendModel","p":"App/Models/Frontend/FrontendModel"},{"l":4,"n":"HomeModel","p":"App/Models/Frontend/HomeModel"}]},{"l":3,"n":"BaseModel","p":"App/Models/BaseModel"}]},{"l":2,"n":"Validation","p":"App/Validation","c":[{"l":3,"n":"Backend","p":"App/Validation/Backend","c":[{"l":4,"n":"AdminsRules","p":"App/Validation/Backend/AdminsRules"},{"l":4,"n":"AuthRules","p":"App/Validation/Backend/AuthRules"},{"l":4,"n":"CustomRules","p":"App/Validation/Backend/CustomRules"},{"l":4,"n":"GroupsRules","p":"App/Validation/Backend/GroupsRules"},{"l":4,"n":"ImagesRules","p":"App/Validation/Backend/ImagesRules"},{"l":4,"n":"SettingsRules","p":"App/Validation/Backend/SettingsRules"}]}]}]},{"l":1,"n":"Config","p":"Config","c":[{"l":2,"n":"Backend","p":"Config/Backend","c":[{"l":3,"n":"Assets","p":"Config/Backend/Assets"},{"l":3,"n":"Auth","p":"Config/Backend/Auth"},{"l":3,"n":"Dashboard","p":"Config/Backend/Dashboard"},{"l":3,"n":"Email","p":"Config/Backend/Email"},{"l":3,"n":"Export","p":"Config/Backend/Export"},{"l":3,"n":"General","p":"Config/Backend/General"},{"l":3,"n":"Import","p":"Config/Backend/Import"},{"l":3,"n":"Menu","p":"Config/Backend/Menu"},{"l":3,"n":"Permissions","p":"Config/Backend/Permissions"},{"l":3,"n":"Upload","p":"Config/Backend/Upload"}]},{"l":2,"n":"Services","p":"Config/Services"}]}]},"treeOpenLevel":1},
    /** @var boolean */
    treeLoaded: false,
    /** @var boolean */
    listenersRegistered: false,
    autoCompleteData: null,
    /** @var boolean */
    autoCompleteLoading: false,
    /** @var boolean */
    autoCompleteLoaded: false,
    /** @var string|null */
    rootPath: null,
    /** @var string|null */
    autoCompleteDataUrl: null,
    /** @var HTMLElement|null */
    doctumSearchAutoComplete: null,
    /** @var HTMLElement|null */
    doctumSearchAutoCompleteProgressBarContainer: null,
    /** @var HTMLElement|null */
    doctumSearchAutoCompleteProgressBar: null,
    /** @var number */
    doctumSearchAutoCompleteProgressBarPercent: 0,
    /** @var autoComplete|null */
    autoCompleteJS: null,
    querySearchSecurityRegex: /([^0-9a-zA-Z:\\\\_\s])/gi,
    buildTreeNode: function (treeNode, htmlNode, treeOpenLevel) {
        var ulNode = document.createElement('ul');
        for (var childKey in treeNode.c) {
            var child = treeNode.c[childKey];
            var liClass = document.createElement('li');
            var hasChildren = child.hasOwnProperty('c');
            var nodeSpecialName = (hasChildren ? 'namespace:' : 'class:') + child.p.replace(/\//g, '_');
            liClass.setAttribute('data-name', nodeSpecialName);

            // Create the node that will have the text
            var divHd = document.createElement('div');
            var levelCss = child.l - 1;
            divHd.className = hasChildren ? 'hd' : 'hd leaf';
            divHd.style.paddingLeft = (hasChildren ? (levelCss * 18) : (8 + (levelCss * 18))) + 'px';
            if (hasChildren) {
                if (child.l <= treeOpenLevel) {
                    liClass.className = 'opened';
                }
                var spanIcon = document.createElement('span');
                spanIcon.className = 'icon icon-play';
                divHd.appendChild(spanIcon);
            }
            var aLink = document.createElement('a');

            // Edit the HTML link to work correctly based on the current depth
            aLink.href = Doctum.rootPath + child.p + '.html';
            aLink.innerText = child.n;
            divHd.appendChild(aLink);
            liClass.appendChild(divHd);

            // It has children
            if (hasChildren) {
                var divBd = document.createElement('div');
                divBd.className = 'bd';
                Doctum.buildTreeNode(child, divBd, treeOpenLevel);
                liClass.appendChild(divBd);
            }
            ulNode.appendChild(liClass);
        }
        htmlNode.appendChild(ulNode);
    },
    initListeners: function () {
        if (Doctum.listenersRegistered) {
            // Quick exit, already registered
            return;
        }
                Doctum.listenersRegistered = true;
    },
    loadTree: function () {
        if (Doctum.treeLoaded) {
            // Quick exit, already registered
            return;
        }
        Doctum.rootPath = document.body.getAttribute('data-root-path');
        Doctum.buildTreeNode(Doctum.treeJson.tree, document.getElementById('api-tree'), Doctum.treeJson.treeOpenLevel);

        // Toggle left-nav divs on click
        $('#api-tree .hd span').on('click', function () {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }
        Doctum.treeLoaded = true;
    },
    pagePartiallyLoaded: function (event) {
        Doctum.initListeners();
        Doctum.loadTree();
        Doctum.loadAutoComplete();
    },
    pageFullyLoaded: function (event) {
        // it may not have received DOMContentLoaded event
        Doctum.initListeners();
        Doctum.loadTree();
        Doctum.loadAutoComplete();
        // Fire the event in the search page too
        if (typeof DoctumSearch === 'object') {
            DoctumSearch.pageFullyLoaded();
        }
    },
    loadAutoComplete: function () {
        if (Doctum.autoCompleteLoaded) {
            // Quick exit, already loaded
            return;
        }
        Doctum.autoCompleteDataUrl = document.body.getAttribute('data-search-index-url');
        Doctum.doctumSearchAutoComplete = document.getElementById('doctum-search-auto-complete');
        Doctum.doctumSearchAutoCompleteProgressBarContainer = document.getElementById('search-progress-bar-container');
        Doctum.doctumSearchAutoCompleteProgressBar = document.getElementById('search-progress-bar');
        if (Doctum.doctumSearchAutoComplete !== null) {
            // Wait for it to be loaded
            Doctum.doctumSearchAutoComplete.addEventListener('init', function (_) {
                Doctum.autoCompleteLoaded = true;
                Doctum.doctumSearchAutoComplete.addEventListener('selection', function (event) {
                    // Go to selection page
                    window.location = Doctum.rootPath + event.detail.selection.value.p;
                });
                Doctum.doctumSearchAutoComplete.addEventListener('navigate', function (event) {
                    // Set selection in text box
                    if (typeof event.detail.selection.value === 'object') {
                        Doctum.doctumSearchAutoComplete.value = event.detail.selection.value.n;
                    }
                });
                Doctum.doctumSearchAutoComplete.addEventListener('results', function (event) {
                    Doctum.markProgressFinished();
                });
            });
        }
        // Check if the lib is loaded
        if (typeof autoComplete === 'function') {
            Doctum.bootAutoComplete();
        }
    },
    markInProgress: function () {
            Doctum.doctumSearchAutoCompleteProgressBarContainer.className = 'search-bar';
            Doctum.doctumSearchAutoCompleteProgressBar.className = 'progress-bar indeterminate';
            if (typeof DoctumSearch === 'object' && DoctumSearch.pageFullyLoaded) {
                DoctumSearch.doctumSearchPageAutoCompleteProgressBarContainer.className = 'search-bar';
                DoctumSearch.doctumSearchPageAutoCompleteProgressBar.className = 'progress-bar indeterminate';
            }
    },
    markProgressFinished: function () {
        Doctum.doctumSearchAutoCompleteProgressBarContainer.className = 'search-bar hidden';
        Doctum.doctumSearchAutoCompleteProgressBar.className = 'progress-bar';
        if (typeof DoctumSearch === 'object' && DoctumSearch.pageFullyLoaded) {
            DoctumSearch.doctumSearchPageAutoCompleteProgressBarContainer.className = 'search-bar hidden';
            DoctumSearch.doctumSearchPageAutoCompleteProgressBar.className = 'progress-bar';
        }
    },
    makeProgess: function () {
        Doctum.makeProgressOnProgressBar(
            Doctum.doctumSearchAutoCompleteProgressBarPercent,
            Doctum.doctumSearchAutoCompleteProgressBar
        );
        if (typeof DoctumSearch === 'object' && DoctumSearch.pageFullyLoaded) {
            Doctum.makeProgressOnProgressBar(
                Doctum.doctumSearchAutoCompleteProgressBarPercent,
                DoctumSearch.doctumSearchPageAutoCompleteProgressBar
            );
        }
    },
    loadAutoCompleteData: function (query) {
        return new Promise(function (resolve, reject) {
            if (Doctum.autoCompleteData !== null) {
                resolve(Doctum.autoCompleteData);
                return;
            }
            Doctum.markInProgress();
            function reqListener() {
                Doctum.autoCompleteLoading = false;
                Doctum.autoCompleteData = JSON.parse(this.responseText).items;
                Doctum.markProgressFinished();

                setTimeout(function () {
                    resolve(Doctum.autoCompleteData);
                }, 50);// Let the UI render once before sending the results for processing. This gives time to the progress bar to hide
            }
            function reqError(err) {
                Doctum.autoCompleteLoading = false;
                Doctum.autoCompleteData = null;
                console.error(err);
                reject(err);
            }

            var oReq = new XMLHttpRequest();
            oReq.onload = reqListener;
            oReq.onerror = reqError;
            oReq.onprogress = function (pe) {
                if (pe.lengthComputable) {
                    Doctum.doctumSearchAutoCompleteProgressBarPercent = parseInt(pe.loaded / pe.total * 100, 10);
                    Doctum.makeProgess();
                }
            };
            oReq.onloadend = function (_) {
                Doctum.markProgressFinished();
            };
            oReq.open('get', Doctum.autoCompleteDataUrl, true);
            oReq.send();
        });
    },
    /**
     * Make some progress on a progress bar
     *
     * @param number percentage
     * @param HTMLElement progressBar
     * @return void
     */
    makeProgressOnProgressBar: function(percentage, progressBar) {
        progressBar.className = 'progress-bar';
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute(
            'aria-valuenow', percentage
        );
    },
    searchEngine: function (query, record) {
        if (typeof query !== 'string') {
            return '';
        }
        // replace all (mode = g) spaces and non breaking spaces (\s) by pipes
        // g = global mode to mark also the second word searched
        // i = case insensitive
        // how this function works:
        // First: search if the query has the keywords in sequence
        // Second: replace the keywords by a mark and leave all the text in between non marked
        
        if (record.match(new RegExp('(' + query.replace(/\s/g, ').*(') + ')', 'gi')) === null) {
            return '';// Does not match
        }

        var replacedRecord = record.replace(new RegExp('(' + query.replace(/\s/g, '|') + ')', 'gi'), function (group) {
            return '<mark class="auto-complete-highlight">' + group + '</mark>';
        });

        if (replacedRecord !== record) {
            return replacedRecord;// This should not happen but just in case there was no match done
        }

        return '';
    },
    /**
     * Clean the search query
     *
     * @param string|null query
     * @return string
     */
    cleanSearchQuery: function (query) {
        if (typeof query !== 'string') {
            return '';
        }
        // replace any chars that could lead to injecting code in our regex
        // remove start or end spaces
        // replace backslashes by an escaped version, use case in search: \myRootFunction
        return query.replace(Doctum.querySearchSecurityRegex, '').trim().replace(/\\/g, '\\\\');
    },
    bootAutoComplete: function () {
        Doctum.autoCompleteJS = new autoComplete(
            {
                selector: '#doctum-search-auto-complete',
                searchEngine: function (query, record) {
                    return Doctum.searchEngine(query, record);
                },
                submit: true,
                data: {
                    src: function (q) {
                        Doctum.markInProgress();
                        return Doctum.loadAutoCompleteData(q);
                    },
                    keys: ['n'],// Data 'Object' key to be searched
                    cache: false, // Is not compatible with async fetch of data
                },
                query: (input) => {
                    return Doctum.cleanSearchQuery(input);
                },
                trigger: (query) => {
                    return Doctum.cleanSearchQuery(query).length > 0;
                },
                resultsList: {
                    tag: 'ul',
                    class: 'auto-complete-dropdown-menu',
                    destination: '#auto-complete-results',
                    position: 'afterbegin',
                    maxResults: 500,
                    noResults: false,
                },
                resultItem: {
                    tag: 'li',
                    class: 'auto-complete-result',
                    highlight: 'auto-complete-highlight',
                    selected: 'auto-complete-selected'
                },
            }
        );
    }
};


document.addEventListener('DOMContentLoaded', Doctum.pagePartiallyLoaded, false);
window.addEventListener('load', Doctum.pageFullyLoaded, false);
