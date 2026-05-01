const handleThemeUpdate = (cssVars) => {
    const root = document.querySelector(':root');
    const keys = Object.keys(cssVars);
    keys.forEach(key => {
        root.style.setProperty(key, cssVars[key]);
    });
}

function dynamicPrimaryColor(primaryColor) {
    primaryColor.forEach((item) => {
        item.addEventListener('input', (e) => {
            const cssPropName = `--primary-${e.target.getAttribute('data-id')}`;
            const cssPropName1 = `--primary-${e.target.getAttribute('data-id1')}`;
            const cssPropName2 = `--primary-${e.target.getAttribute('data-id2')}`;
            const cssPropName7 = `--primary-${e.target.getAttribute('data-id7')}`;
            const cssPropName8 = `--darkprimary-${e.target.getAttribute('data-id8')}`;
            const cssPropName3 = `--dark-${e.target.getAttribute('data-id3')}`;
            const cssPropName4 = `--transparent-${e.target.getAttribute('data-id4')}`;
            const cssPropName5 = `--dark-${e.target.getAttribute('data-id5')}`;
            const cssPropName6 = `--dark-${e.target.getAttribute('data-id6')}`;
            const cssPropName9 = `--transparentprimary-${e.target.getAttribute('data-id9')}`;
            handleThemeUpdate({
                [cssPropName]: e.target.value,
                [cssPropName1]: e.target.value + 95,
                [cssPropName2]: e.target.value,
                [cssPropName3]: e.target.value,
                [cssPropName4]: e.target.value,
                [cssPropName5]: e.target.value,
                [cssPropName6]: hexToRgba(e.target.value, 0.5),
                [cssPropName7]: e.target.value + 20,
                [cssPropName8]: e.target.value + 20,
                [cssPropName9]: e.target.value + 20,
            });
        });
    });
}

(function () {
    "use strict";

    // Light theme color picker
    const dynamicPrimaryLight = document.querySelectorAll('input.color-primary-light');

    // themeSwitch(LightThemeSwitchers);
    dynamicPrimaryColor(dynamicPrimaryLight);

    // tranparent theme color picker

    const transparentDynamicPrimaryLight = document.querySelectorAll('input.color-primary-transparent');

    // themeSwitch(transparentThemeSwitchers);
    dynamicPrimaryColor(transparentDynamicPrimaryLight);

    // tranparent theme bgcolor picker

    const transparentDynamicPBgLight = document.querySelectorAll('input.color-bg-transparent');

    // themeSwitch(transparentBgThemeSwitchers);
    dynamicPrimaryColor(transparentDynamicPBgLight);

    localStorageBackup();

})();

function localStorageBackup() {
    "use strict";

    // if there is a value stored, update color picker and background color
    // Used to retrive the data from local storage
    if (localStorage.azeaprimaryColor) {
        // document.getElementById('colorID').value = localStorage.azeaprimaryColor;
        document.querySelector('html').style.setProperty('--primary-bg-color', localStorage.azeaprimaryColor);
        document.querySelector('html').style.setProperty('--primary-bg-hover', localStorage.azeaprimaryHoverColor);
        document.querySelector('html').style.setProperty('--primary-bg-border', localStorage.azeaprimaryBorderColor);
        document.querySelector('html').style.setProperty('--primary-transparentcolor', localStorage.azeaprimaryTransparent);
        document.querySelector("body")?.classList.add("light-mode");

        $('#myonoffswitch3').prop('checked', true);
        $('#myonoffswitch6').prop('checked', true);
        $('#myonoffswitch1').prop('checked', true);
    }

    if (localStorage.azeadarkBackgroundColor) {
        // document.getElementById('transparentBgColorID').value = localStorage.azeadarkBackgroundColor;
        document.querySelector('html').style.setProperty('--dark-body', localStorage.azeadarkBackgroundColor);
        document.querySelector('body').classList.add('dark-mode');
        document.querySelector('body').classList.remove('light-mode');
        $('#myonoffswitch2').prop('checked', true);
    }

    if (localStorage.azealightMode) {
        document.querySelector('body')?.classList.add('light-mode');
        document.querySelector('body')?.classList.remove('dark-mode');
    }
    if (localStorage.azeadarkMode) {
        document.querySelector('body')?.classList.remove('light-mode');
        document.querySelector('body')?.classList.add('dark-mode');
        $('#myonoffswitch2').prop('checked', true);
        $('#myonoffswitch5').prop('checked', true);
        $('#myonoffswitch8').prop('checked', true);
    }
    if(localStorage.azeahorizontal){
        document.querySelector('body').classList.add('horizontal')
    }
    if(localStorage.azeahorizontalHover){
        document.querySelector('body').classList.add('horizontal-hover')
    }
    if(localStorage.azeartl){
        document.querySelector('body').classList.add('rtl');
        $('#myonoffswitch55').prop('checked', true)
    }
}

// triggers on changing the color picker
function changePrimaryColor() {
    "use strict";

    $('#myonoffswitch3').prop('checked', true);
    $('#myonoffswitch6').prop('checked', true);
    checkOptions();

    var userColor = document.getElementById('colorID').value;
    localStorage.setItem('azeaprimaryColor', userColor);
    // to store value as opacity 0.95 we use 95
    localStorage.setItem('azeaprimaryHoverColor', userColor + 95);
    localStorage.setItem('azeaprimaryBorderColor', userColor);
    localStorage.setItem('azeaprimaryTransparent', userColor + 20);

    document.querySelector('body').classList.add('light-mode');

    $('#myonoffswitch1').prop('checked', true);
    names()

    localStorage.setItem('azealightMode', true);
}

function transparentBgColor() {
    "use strict";

    $('#myonoffswitch3').prop('checked', false);
    $('#myonoffswitch6').prop('checked', false);
    $('#myonoffswitch5').prop('checked', true);
    $('#myonoffswitch8').prop('checked', true);
    var userColor = document.getElementById('transparentBgColorID').value;
    localStorage.setItem('azeadarkBackgroundColor', userColor);

    // removing light theme data 
    // localStorage.removeItem('azeadarkPrimary');
    localStorage.removeItem('azeaprimaryColor')
    localStorage.removeItem('azeaprimaryHoverColor')
    localStorage.removeItem('azeaprimaryBorderColor')
    localStorage.removeItem('azeaprimaryTransparent');
    document.querySelector('body').classList.remove('light-mode');
    document.querySelector('body').classList.add('dark-mode');

    $('#myonoffswitch2').prop('checked', true);
    checkOptions();
    if ($('body').hasClass('bg-img1') || $('body').hasClass('bg-img2') || $('body').hasClass('bg-img3') || $('body').hasClass('bg-img4')) {
        $('body').removeClass('bg-img1')
        $('body').removeClass('bg-img2')
        $('body').removeClass('bg-img3')
        $('body').removeClass('bg-img4')
    }

    localStorage.setItem('azeadarkMode', true);
    localStorage.removeItem('azealightMode');
}

// to check the value is hexa or not


const isValidHex = (hexValue) => /^#([A-Fa-f0-9]{3,4}){1,2}$/.test(hexValue)

const getChunksFromString = (st, chunkSize) => st.match(new RegExp(`.{${chunkSize}}`, "g"))
// convert hex value to 256
const convertHexUnitTo256 = (hexStr) => parseInt(hexStr.repeat(2 / hexStr.length), 16)
// get alpha value is equla to 1 if there was no value is asigned to alpha in function
const getAlphafloat = (a, alpha) => {
    if (typeof a !== "undefined") { return a / 255 }
    if ((typeof alpha != "number") || alpha < 0 || alpha > 1) {
        return 1
    }
    return alpha
}
// convertion of hex code to rgba code 
function hexToRgba(hexValue, alpha) {
    if (!isValidHex(hexValue)) { return null }
    const chunkSize = Math.floor((hexValue.length - 1) / 3)
    const hexArr = getChunksFromString(hexValue.slice(1), chunkSize)
    const [r, g, b, a] = hexArr.map(convertHexUnitTo256)
    return `rgba(${r}, ${g}, ${b}, ${getAlphafloat(a, alpha)})`
}


let myVarVal, myVarVal1, myVarVal2, myVarVal3

function names() {
    "use strict";

    let primaryColorVal = getComputedStyle(document.documentElement).getPropertyValue('--primary-bg-color').trim();

    //get variable
    myVarVal = localStorage.getItem("azeaprimaryColor") || localStorage.getItem("azeadarkPrimary") || localStorage.getItem("azeatransparentPrimary") || localStorage.getItem("azeatransparentBgImgPrimary") || primaryColorVal;

    if (document.querySelector('#statistics') !== null) {
        sales();
    }

    let colorData = hexToRgba(myVarVal || primaryColorVal, 0.1)
    document.querySelector('html').style.setProperty('--primary01', colorData);

    let colorData1 = hexToRgba(myVarVal || primaryColorVal, 0.2)
    document.querySelector('html').style.setProperty('--primary02', colorData1);

    let colorData2 = hexToRgba(myVarVal || primaryColorVal, 0.3)
    document.querySelector('html').style.setProperty('--primary03', colorData2);

    let colorData3 = hexToRgba(myVarVal || primaryColorVal, 0.6)
    document.querySelector('html').style.setProperty('--primary06', colorData3);

    let colorData4 = hexToRgba(myVarVal || primaryColorVal, 0.9)
    document.querySelector('html').style.setProperty('--primary09', colorData4);

}
names()
