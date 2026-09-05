var H;
(function(w) {
  w[w.QR_CODE = 0] = "QR_CODE", w[w.AZTEC = 1] = "AZTEC", w[w.CODABAR = 2] = "CODABAR", w[w.CODE_39 = 3] = "CODE_39", w[w.CODE_93 = 4] = "CODE_93", w[w.CODE_128 = 5] = "CODE_128", w[w.DATA_MATRIX = 6] = "DATA_MATRIX", w[w.MAXICODE = 7] = "MAXICODE", w[w.ITF = 8] = "ITF", w[w.EAN_13 = 9] = "EAN_13", w[w.EAN_8 = 10] = "EAN_8", w[w.PDF_417 = 11] = "PDF_417", w[w.RSS_14 = 12] = "RSS_14", w[w.RSS_EXPANDED = 13] = "RSS_EXPANDED", w[w.UPC_A = 14] = "UPC_A", w[w.UPC_E = 15] = "UPC_E", w[w.UPC_EAN_EXTENSION = 16] = "UPC_EAN_EXTENSION";
})(H || (H = {}));
var Tr = /* @__PURE__ */ new Map([
  [H.QR_CODE, "QR_CODE"],
  [H.AZTEC, "AZTEC"],
  [H.CODABAR, "CODABAR"],
  [H.CODE_39, "CODE_39"],
  [H.CODE_93, "CODE_93"],
  [H.CODE_128, "CODE_128"],
  [H.DATA_MATRIX, "DATA_MATRIX"],
  [H.MAXICODE, "MAXICODE"],
  [H.ITF, "ITF"],
  [H.EAN_13, "EAN_13"],
  [H.EAN_8, "EAN_8"],
  [H.PDF_417, "PDF_417"],
  [H.RSS_14, "RSS_14"],
  [H.RSS_EXPANDED, "RSS_EXPANDED"],
  [H.UPC_A, "UPC_A"],
  [H.UPC_E, "UPC_E"],
  [H.UPC_EAN_EXTENSION, "UPC_EAN_EXTENSION"]
]), Nr;
(function(w) {
  w[w.UNKNOWN = 0] = "UNKNOWN", w[w.URL = 1] = "URL";
})(Nr || (Nr = {}));
function Ci(w) {
  return Object.values(H).includes(w);
}
var Rn;
(function(w) {
  w[w.SCAN_TYPE_CAMERA = 0] = "SCAN_TYPE_CAMERA", w[w.SCAN_TYPE_FILE = 1] = "SCAN_TYPE_FILE";
})(Rn || (Rn = {}));
var mi = function() {
  function w() {
  }
  return w.GITHUB_PROJECT_URL = "https://github.com/mebjas/html5-qrcode", w.SCAN_DEFAULT_FPS = 2, w.DEFAULT_DISABLE_FLIP = !1, w.DEFAULT_REMEMBER_LAST_CAMERA_USED = !0, w.DEFAULT_SUPPORTED_SCAN_TYPE = [
    Rn.SCAN_TYPE_CAMERA,
    Rn.SCAN_TYPE_FILE
  ], w;
}(), Ur = function() {
  function w(x, h) {
    this.format = x, this.formatName = h;
  }
  return w.prototype.toString = function() {
    return this.formatName;
  }, w.create = function(x) {
    if (!Tr.has(x))
      throw "".concat(x, " not in html5QrcodeSupportedFormatsTextMap");
    return new w(x, Tr.get(x));
  }, w;
}(), _r = function() {
  function w() {
  }
  return w.createFromText = function(x) {
    var h = {
      text: x
    };
    return {
      decodedText: x,
      result: h
    };
  }, w.createFromQrcodeResult = function(x) {
    return {
      decodedText: x.text,
      result: x
    };
  }, w;
}(), nr;
(function(w) {
  w[w.UNKWOWN_ERROR = 0] = "UNKWOWN_ERROR", w[w.IMPLEMENTATION_ERROR = 1] = "IMPLEMENTATION_ERROR", w[w.NO_CODE_FOUND_ERROR = 2] = "NO_CODE_FOUND_ERROR";
})(nr || (nr = {}));
var Ii = function() {
  function w() {
  }
  return w.createFrom = function(x) {
    return {
      errorMessage: x,
      type: nr.UNKWOWN_ERROR
    };
  }, w;
}(), pi = function() {
  function w(x) {
    this.verbose = x;
  }
  return w.prototype.log = function(x) {
    this.verbose && console.log(x);
  }, w.prototype.warn = function(x) {
    this.verbose && console.warn(x);
  }, w.prototype.logError = function(x, h) {
    (this.verbose || h === !0) && console.error(x);
  }, w.prototype.logErrors = function(x) {
    if (x.length === 0)
      throw "Logger#logError called without arguments";
    this.verbose && console.error(x);
  }, w;
}();
function bt(w) {
  return typeof w > "u" || w === null;
}
var en = function() {
  function w() {
  }
  return w.codeParseError = function(x) {
    return "QR code parse error, error = ".concat(x);
  }, w.errorGettingUserMedia = function(x) {
    return "Error getting userMedia, error = ".concat(x);
  }, w.onlyDeviceSupportedError = function() {
    return "The device doesn't support navigator.mediaDevices , only supported cameraIdOrConfig in this case is deviceId parameter (string).";
  }, w.cameraStreamingNotSupported = function() {
    return "Camera streaming not supported by the browser.";
  }, w.unableToQuerySupportedDevices = function() {
    return "Unable to query supported devices, unknown error.";
  }, w.insecureContextCameraQueryError = function() {
    return "Camera access is only supported in secure context like https or localhost.";
  }, w.scannerPaused = function() {
    return "Scanner paused";
  }, w;
}(), Vr = function() {
  function w() {
  }
  return w.isMediaStreamConstraintsValid = function(x, h) {
    if (typeof x != "object") {
      var m = typeof x;
      return h.logError("videoConstraints should be of type object, the " + "object passed is of type ".concat(m, "."), !0), !1;
    }
    for (var I = [
      "autoGainControl",
      "channelCount",
      "echoCancellation",
      "latency",
      "noiseSuppression",
      "sampleRate",
      "sampleSize",
      "volume"
    ], g = new Set(I), T = Object.keys(x), M = 0, B = T; M < B.length; M++) {
      var E = B[M];
      if (g.has(E))
        return h.logError("".concat(E, " is not supported videoConstaints."), !0), !1;
    }
    return !0;
  }, w;
}(), gn = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : typeof self < "u" ? self : {}, rr = { exports: {} };
(function(w, x) {
  (function(h, m) {
    m(x);
  })(gn, function(h) {
    function m(u) {
      return u == null;
    }
    var I = Object.setPrototypeOf || { __proto__: [] } instanceof Array && function(u, e) {
      u.__proto__ = e;
    } || function(u, e) {
      for (var t in e) e.hasOwnProperty(t) && (u[t] = e[t]);
    };
    function g(u, e) {
      I(u, e);
      function t() {
        this.constructor = u;
      }
      u.prototype = e === null ? Object.create(e) : (t.prototype = e.prototype, new t());
    }
    function T(u, e) {
      var t = Object.setPrototypeOf;
      t ? t(u, e) : u.__proto__ = e;
    }
    function M(u, e) {
      e === void 0 && (e = u.constructor);
      var t = Error.captureStackTrace;
      t && t(u, e);
    }
    var B = function(u) {
      g(e, u);
      function e(t) {
        var n = this.constructor, r = u.call(this, t) || this;
        return Object.defineProperty(r, "name", {
          value: n.name,
          enumerable: !1
        }), T(r, n.prototype), M(r), r;
      }
      return e;
    }(Error);
    class E extends B {
      /**
       * Allows Exception to be constructed directly
       * with some message and prototype definition.
       */
      constructor(e = void 0) {
        super(e), this.message = e;
      }
      getKind() {
        return this.constructor.kind;
      }
    }
    E.kind = "Exception";
    class R extends E {
    }
    R.kind = "ArgumentException";
    class D extends E {
    }
    D.kind = "IllegalArgumentException";
    class ne {
      constructor(e) {
        if (this.binarizer = e, e === null)
          throw new D("Binarizer must be non-null.");
      }
      /**
       * @return The width of the bitmap.
       */
      getWidth() {
        return this.binarizer.getWidth();
      }
      /**
       * @return The height of the bitmap.
       */
      getHeight() {
        return this.binarizer.getHeight();
      }
      /**
       * Converts one row of luminance data to 1 bit data. May actually do the conversion, or return
       * cached data. Callers should assume this method is expensive and call it as seldom as possible.
       * This method is intended for decoding 1D barcodes and may choose to apply sharpening.
       *
       * @param y The row to fetch, which must be in [0, bitmap height)
       * @param row An optional preallocated array. If null or too small, it will be ignored.
       *            If used, the Binarizer will call BitArray.clear(). Always use the returned object.
       * @return The array of bits for this row (true means black).
       * @throws NotFoundException if row can't be binarized
       */
      getBlackRow(e, t) {
        return this.binarizer.getBlackRow(e, t);
      }
      /**
       * Converts a 2D array of luminance data to 1 bit. As above, assume this method is expensive
       * and do not call it repeatedly. This method is intended for decoding 2D barcodes and may or
       * may not apply sharpening. Therefore, a row from this matrix may not be identical to one
       * fetched using getBlackRow(), so don't mix and match between them.
       *
       * @return The 2D array of bits for the image (true means black).
       * @throws NotFoundException if image can't be binarized to make a matrix
       */
      getBlackMatrix() {
        return (this.matrix === null || this.matrix === void 0) && (this.matrix = this.binarizer.getBlackMatrix()), this.matrix;
      }
      /**
       * @return Whether this bitmap can be cropped.
       */
      isCropSupported() {
        return this.binarizer.getLuminanceSource().isCropSupported();
      }
      /**
       * Returns a new object with cropped image data. Implementations may keep a reference to the
       * original data rather than a copy. Only callable if isCropSupported() is true.
       *
       * @param left The left coordinate, which must be in [0,getWidth())
       * @param top The top coordinate, which must be in [0,getHeight())
       * @param width The width of the rectangle to crop.
       * @param height The height of the rectangle to crop.
       * @return A cropped version of this object.
       */
      crop(e, t, n, r) {
        const i = this.binarizer.getLuminanceSource().crop(e, t, n, r);
        return new ne(this.binarizer.createBinarizer(i));
      }
      /**
       * @return Whether this bitmap supports counter-clockwise rotation.
       */
      isRotateSupported() {
        return this.binarizer.getLuminanceSource().isRotateSupported();
      }
      /**
       * Returns a new object with rotated image data by 90 degrees counterclockwise.
       * Only callable if {@link #isRotateSupported()} is true.
       *
       * @return A rotated version of this object.
       */
      rotateCounterClockwise() {
        const e = this.binarizer.getLuminanceSource().rotateCounterClockwise();
        return new ne(this.binarizer.createBinarizer(e));
      }
      /**
       * Returns a new object with rotated image data by 45 degrees counterclockwise.
       * Only callable if {@link #isRotateSupported()} is true.
       *
       * @return A rotated version of this object.
       */
      rotateCounterClockwise45() {
        const e = this.binarizer.getLuminanceSource().rotateCounterClockwise45();
        return new ne(this.binarizer.createBinarizer(e));
      }
      /*@Override*/
      toString() {
        try {
          return this.getBlackMatrix().toString();
        } catch {
          return "";
        }
      }
    }
    class J extends E {
      static getChecksumInstance() {
        return new J();
      }
    }
    J.kind = "ChecksumException";
    class Be {
      constructor(e) {
        this.source = e;
      }
      getLuminanceSource() {
        return this.source;
      }
      getWidth() {
        return this.source.getWidth();
      }
      getHeight() {
        return this.source.getHeight();
      }
    }
    class re {
      // public static void arraycopy(Object src, int srcPos, Object dest, int destPos, int length)
      /**
       * Makes a copy of a array.
       */
      static arraycopy(e, t, n, r, i) {
        for (; i--; )
          n[r++] = e[t++];
      }
      /**
       * Returns the current time in milliseconds.
       */
      static currentTimeMillis() {
        return Date.now();
      }
    }
    class xt extends E {
    }
    xt.kind = "IndexOutOfBoundsException";
    class yt extends xt {
      constructor(e = void 0, t = void 0) {
        super(t), this.index = e, this.message = t;
      }
    }
    yt.kind = "ArrayIndexOutOfBoundsException";
    class me {
      /**
       * Assigns the specified int value to each element of the specified array
       * of ints.
       *
       * @param a the array to be filled
       * @param val the value to be stored in all elements of the array
       */
      static fill(e, t) {
        for (let n = 0, r = e.length; n < r; n++)
          e[n] = t;
      }
      /**
       * Assigns the specified int value to each element of the specified
       * range of the specified array of ints.  The range to be filled
       * extends from index {@code fromIndex}, inclusive, to index
       * {@code toIndex}, exclusive.  (If {@code fromIndex==toIndex}, the
       * range to be filled is empty.)
       *
       * @param a the array to be filled
       * @param fromIndex the index of the first element (inclusive) to be
       *        filled with the specified value
       * @param toIndex the index of the last element (exclusive) to be
       *        filled with the specified value
       * @param val the value to be stored in all elements of the array
       * @throws IllegalArgumentException if {@code fromIndex > toIndex}
       * @throws ArrayIndexOutOfBoundsException if {@code fromIndex < 0} or
       *         {@code toIndex > a.length}
       */
      static fillWithin(e, t, n, r) {
        me.rangeCheck(e.length, t, n);
        for (let i = t; i < n; i++)
          e[i] = r;
      }
      /**
       * Checks that {@code fromIndex} and {@code toIndex} are in
       * the range and throws an exception if they aren't.
       */
      static rangeCheck(e, t, n) {
        if (t > n)
          throw new D("fromIndex(" + t + ") > toIndex(" + n + ")");
        if (t < 0)
          throw new yt(t);
        if (n > e)
          throw new yt(n);
      }
      static asList(...e) {
        return e;
      }
      static create(e, t, n) {
        return Array.from({ length: e }).map((i) => Array.from({ length: t }).fill(n));
      }
      static createInt32Array(e, t, n) {
        return Array.from({ length: e }).map((i) => Int32Array.from({ length: t }).fill(n));
      }
      static equals(e, t) {
        if (!e || !t || !e.length || !t.length || e.length !== t.length)
          return !1;
        for (let n = 0, r = e.length; n < r; n++)
          if (e[n] !== t[n])
            return !1;
        return !0;
      }
      static hashCode(e) {
        if (e === null)
          return 0;
        let t = 1;
        for (const n of e)
          t = 31 * t + n;
        return t;
      }
      static fillUint8Array(e, t) {
        for (let n = 0; n !== e.length; n++)
          e[n] = t;
      }
      static copyOf(e, t) {
        return e.slice(0, t);
      }
      static copyOfUint8Array(e, t) {
        if (e.length <= t) {
          const n = new Uint8Array(t);
          return n.set(e), n;
        }
        return e.slice(0, t);
      }
      static copyOfRange(e, t, n) {
        const r = n - t, i = new Int32Array(r);
        return re.arraycopy(e, t, i, 0, r), i;
      }
      /*
      * Returns the index of of the element in a sorted array or (-n-1) where n is the insertion point
      * for the new element.
      * Parameters:
      *     ar - A sorted array
      *     el - An element to search for
      *     comparator - A comparator function. The function takes two arguments: (a, b) and returns:
      *        a negative number  if a is less than b;
      *        0 if a is equal to b;
      *        a positive number of a is greater than b.
      * The array may contain duplicate elements. If there are more than one equal elements in the array,
      * the returned value can be the index of any one of the equal elements.
      *
      * http://jsfiddle.net/aryzhov/pkfst550/
      */
      static binarySearch(e, t, n) {
        n === void 0 && (n = me.numberComparator);
        let r = 0, i = e.length - 1;
        for (; r <= i; ) {
          const s = i + r >> 1, o = n(t, e[s]);
          if (o > 0)
            r = s + 1;
          else if (o < 0)
            i = s - 1;
          else
            return s;
        }
        return -r - 1;
      }
      static numberComparator(e, t) {
        return e - t;
      }
    }
    class j {
      static numberOfTrailingZeros(e) {
        let t;
        if (e === 0)
          return 32;
        let n = 31;
        return t = e << 16, t !== 0 && (n -= 16, e = t), t = e << 8, t !== 0 && (n -= 8, e = t), t = e << 4, t !== 0 && (n -= 4, e = t), t = e << 2, t !== 0 && (n -= 2, e = t), n - (e << 1 >>> 31);
      }
      static numberOfLeadingZeros(e) {
        if (e === 0)
          return 32;
        let t = 1;
        return e >>> 16 || (t += 16, e <<= 16), e >>> 24 || (t += 8, e <<= 8), e >>> 28 || (t += 4, e <<= 4), e >>> 30 || (t += 2, e <<= 2), t -= e >>> 31, t;
      }
      static toHexString(e) {
        return e.toString(16);
      }
      static toBinaryString(e) {
        return String(parseInt(String(e), 2));
      }
      // Returns the number of one-bits in the two's complement binary representation of the specified int value. This function is sometimes referred to as the population count.
      // Returns:
      // the number of one-bits in the two's complement binary representation of the specified int value.
      static bitCount(e) {
        return e = e - (e >>> 1 & 1431655765), e = (e & 858993459) + (e >>> 2 & 858993459), e = e + (e >>> 4) & 252645135, e = e + (e >>> 8), e = e + (e >>> 16), e & 63;
      }
      static truncDivision(e, t) {
        return Math.trunc(e / t);
      }
      /**
       * Converts A string to an integer.
       * @param s A string to convert into a number.
       * @param radix A value between 2 and 36 that specifies the base of the number in numString. If this argument is not supplied, strings with a prefix of '0x' are considered hexadecimal. All other strings are considered decimal.
       */
      static parseInt(e, t = void 0) {
        return parseInt(e, t);
      }
    }
    j.MIN_VALUE_32_BITS = -2147483648, j.MAX_VALUE = Number.MAX_SAFE_INTEGER;
    class le {
      // For testing only
      constructor(e, t) {
        e === void 0 ? (this.size = 0, this.bits = new Int32Array(1)) : (this.size = e, t == null ? this.bits = le.makeArray(e) : this.bits = t);
      }
      getSize() {
        return this.size;
      }
      getSizeInBytes() {
        return Math.floor((this.size + 7) / 8);
      }
      ensureCapacity(e) {
        if (e > this.bits.length * 32) {
          const t = le.makeArray(e);
          re.arraycopy(this.bits, 0, t, 0, this.bits.length), this.bits = t;
        }
      }
      /**
       * @param i bit to get
       * @return true iff bit i is set
       */
      get(e) {
        return (this.bits[Math.floor(e / 32)] & 1 << (e & 31)) !== 0;
      }
      /**
       * Sets bit i.
       *
       * @param i bit to set
       */
      set(e) {
        this.bits[Math.floor(e / 32)] |= 1 << (e & 31);
      }
      /**
       * Flips bit i.
       *
       * @param i bit to set
       */
      flip(e) {
        this.bits[Math.floor(e / 32)] ^= 1 << (e & 31);
      }
      /**
       * @param from first bit to check
       * @return index of first bit that is set, starting from the given index, or size if none are set
       *  at or beyond this given index
       * @see #getNextUnset(int)
       */
      getNextSet(e) {
        const t = this.size;
        if (e >= t)
          return t;
        const n = this.bits;
        let r = Math.floor(e / 32), i = n[r];
        i &= ~((1 << (e & 31)) - 1);
        const s = n.length;
        for (; i === 0; ) {
          if (++r === s)
            return t;
          i = n[r];
        }
        const o = r * 32 + j.numberOfTrailingZeros(i);
        return o > t ? t : o;
      }
      /**
       * @param from index to start looking for unset bit
       * @return index of next unset bit, or {@code size} if none are unset until the end
       * @see #getNextSet(int)
       */
      getNextUnset(e) {
        const t = this.size;
        if (e >= t)
          return t;
        const n = this.bits;
        let r = Math.floor(e / 32), i = ~n[r];
        i &= ~((1 << (e & 31)) - 1);
        const s = n.length;
        for (; i === 0; ) {
          if (++r === s)
            return t;
          i = ~n[r];
        }
        const o = r * 32 + j.numberOfTrailingZeros(i);
        return o > t ? t : o;
      }
      /**
       * Sets a block of 32 bits, starting at bit i.
       *
       * @param i first bit to set
       * @param newBits the new value of the next 32 bits. Note again that the least-significant bit
       * corresponds to bit i, the next-least-significant to i+1, and so on.
       */
      setBulk(e, t) {
        this.bits[Math.floor(e / 32)] = t;
      }
      /**
       * Sets a range of bits.
       *
       * @param start start of range, inclusive.
       * @param end end of range, exclusive
       */
      setRange(e, t) {
        if (t < e || e < 0 || t > this.size)
          throw new D();
        if (t === e)
          return;
        t--;
        const n = Math.floor(e / 32), r = Math.floor(t / 32), i = this.bits;
        for (let s = n; s <= r; s++) {
          const o = s > n ? 0 : e & 31, l = (2 << (s < r ? 31 : t & 31)) - (1 << o);
          i[s] |= l;
        }
      }
      /**
       * Clears all bits (sets to false).
       */
      clear() {
        const e = this.bits.length, t = this.bits;
        for (let n = 0; n < e; n++)
          t[n] = 0;
      }
      /**
       * Efficient method to check if a range of bits is set, or not set.
       *
       * @param start start of range, inclusive.
       * @param end end of range, exclusive
       * @param value if true, checks that bits in range are set, otherwise checks that they are not set
       * 
       * @return true iff all bits are set or not set in range, according to value argument
       * @throws IllegalArgumentException if end is less than start or the range is not contained in the array
       */
      isRange(e, t, n) {
        if (t < e || e < 0 || t > this.size)
          throw new D();
        if (t === e)
          return !0;
        t--;
        const r = Math.floor(e / 32), i = Math.floor(t / 32), s = this.bits;
        for (let o = r; o <= i; o++) {
          const a = o > r ? 0 : e & 31, c = (2 << (o < i ? 31 : t & 31)) - (1 << a) & 4294967295;
          if ((s[o] & c) !== (n ? c : 0))
            return !1;
        }
        return !0;
      }
      appendBit(e) {
        this.ensureCapacity(this.size + 1), e && (this.bits[Math.floor(this.size / 32)] |= 1 << (this.size & 31)), this.size++;
      }
      /**
       * Appends the least-significant bits, from value, in order from most-significant to
       * least-significant. For example, appending 6 bits from 0x000001E will append the bits
       * 0, 1, 1, 1, 1, 0 in that order.
       *
       * @param value {@code int} containing bits to append
       * @param numBits bits from value to append
       */
      appendBits(e, t) {
        if (t < 0 || t > 32)
          throw new D("Num bits must be between 0 and 32");
        this.ensureCapacity(this.size + t);
        for (let n = t; n > 0; n--)
          this.appendBit((e >> n - 1 & 1) === 1);
      }
      appendBitArray(e) {
        const t = e.size;
        this.ensureCapacity(this.size + t);
        for (let n = 0; n < t; n++)
          this.appendBit(e.get(n));
      }
      xor(e) {
        if (this.size !== e.size)
          throw new D("Sizes don't match");
        const t = this.bits;
        for (let n = 0, r = t.length; n < r; n++)
          t[n] ^= e.bits[n];
      }
      /**
       *
       * @param bitOffset first bit to start writing
       * @param array array to write into. Bytes are written most-significant byte first. This is the opposite
       *  of the internal representation, which is exposed by {@link #getBitArray()}
       * @param offset position in array to start writing
       * @param numBytes how many bytes to write
       */
      toBytes(e, t, n, r) {
        for (let i = 0; i < r; i++) {
          let s = 0;
          for (let o = 0; o < 8; o++)
            this.get(e) && (s |= 1 << 7 - o), e++;
          t[n + i] = /*(byte)*/
          s;
        }
      }
      /**
       * @return underlying array of ints. The first element holds the first 32 bits, and the least
       *         significant bit is bit 0.
       */
      getBitArray() {
        return this.bits;
      }
      /**
       * Reverses all bits in the array.
       */
      reverse() {
        const e = new Int32Array(this.bits.length), t = Math.floor((this.size - 1) / 32), n = t + 1, r = this.bits;
        for (let i = 0; i < n; i++) {
          let s = r[i];
          s = s >> 1 & 1431655765 | (s & 1431655765) << 1, s = s >> 2 & 858993459 | (s & 858993459) << 2, s = s >> 4 & 252645135 | (s & 252645135) << 4, s = s >> 8 & 16711935 | (s & 16711935) << 8, s = s >> 16 & 65535 | (s & 65535) << 16, e[t - i] = /*(int)*/
          s;
        }
        if (this.size !== n * 32) {
          const i = n * 32 - this.size;
          let s = e[0] >>> i;
          for (let o = 1; o < n; o++) {
            const a = e[o];
            s |= a << 32 - i, e[o - 1] = s, s = a >>> i;
          }
          e[n - 1] = s;
        }
        this.bits = e;
      }
      static makeArray(e) {
        return new Int32Array(Math.floor((e + 31) / 32));
      }
      /*@Override*/
      equals(e) {
        if (!(e instanceof le))
          return !1;
        const t = e;
        return this.size === t.size && me.equals(this.bits, t.bits);
      }
      /*@Override*/
      hashCode() {
        return 31 * this.size + me.hashCode(this.bits);
      }
      /*@Override*/
      toString() {
        let e = "";
        for (let t = 0, n = this.size; t < n; t++)
          t & 7 || (e += " "), e += this.get(t) ? "X" : ".";
        return e;
      }
      /*@Override*/
      clone() {
        return new le(this.size, this.bits.slice());
      }
    }
    var St;
    (function(u) {
      u[u.OTHER = 0] = "OTHER", u[u.PURE_BARCODE = 1] = "PURE_BARCODE", u[u.POSSIBLE_FORMATS = 2] = "POSSIBLE_FORMATS", u[u.TRY_HARDER = 3] = "TRY_HARDER", u[u.CHARACTER_SET = 4] = "CHARACTER_SET", u[u.ALLOWED_LENGTHS = 5] = "ALLOWED_LENGTHS", u[u.ASSUME_CODE_39_CHECK_DIGIT = 6] = "ASSUME_CODE_39_CHECK_DIGIT", u[u.ASSUME_GS1 = 7] = "ASSUME_GS1", u[u.RETURN_CODABAR_START_END = 8] = "RETURN_CODABAR_START_END", u[u.NEED_RESULT_POINT_CALLBACK = 9] = "NEED_RESULT_POINT_CALLBACK", u[u.ALLOWED_EAN_EXTENSIONS = 10] = "ALLOWED_EAN_EXTENSIONS";
    })(St || (St = {}));
    var xe = St;
    class U extends E {
      static getFormatInstance() {
        return new U();
      }
    }
    U.kind = "FormatException";
    var de;
    (function(u) {
      u[u.Cp437 = 0] = "Cp437", u[u.ISO8859_1 = 1] = "ISO8859_1", u[u.ISO8859_2 = 2] = "ISO8859_2", u[u.ISO8859_3 = 3] = "ISO8859_3", u[u.ISO8859_4 = 4] = "ISO8859_4", u[u.ISO8859_5 = 5] = "ISO8859_5", u[u.ISO8859_6 = 6] = "ISO8859_6", u[u.ISO8859_7 = 7] = "ISO8859_7", u[u.ISO8859_8 = 8] = "ISO8859_8", u[u.ISO8859_9 = 9] = "ISO8859_9", u[u.ISO8859_10 = 10] = "ISO8859_10", u[u.ISO8859_11 = 11] = "ISO8859_11", u[u.ISO8859_13 = 12] = "ISO8859_13", u[u.ISO8859_14 = 13] = "ISO8859_14", u[u.ISO8859_15 = 14] = "ISO8859_15", u[u.ISO8859_16 = 15] = "ISO8859_16", u[u.SJIS = 16] = "SJIS", u[u.Cp1250 = 17] = "Cp1250", u[u.Cp1251 = 18] = "Cp1251", u[u.Cp1252 = 19] = "Cp1252", u[u.Cp1256 = 20] = "Cp1256", u[u.UnicodeBigUnmarked = 21] = "UnicodeBigUnmarked", u[u.UTF8 = 22] = "UTF8", u[u.ASCII = 23] = "ASCII", u[u.Big5 = 24] = "Big5", u[u.GB18030 = 25] = "GB18030", u[u.EUC_KR = 26] = "EUC_KR";
    })(de || (de = {}));
    class k {
      constructor(e, t, n, ...r) {
        this.valueIdentifier = e, this.name = n, typeof t == "number" ? this.values = Int32Array.from([t]) : this.values = t, this.otherEncodingNames = r, k.VALUE_IDENTIFIER_TO_ECI.set(e, this), k.NAME_TO_ECI.set(n, this);
        const i = this.values;
        for (let s = 0, o = i.length; s !== o; s++) {
          const a = i[s];
          k.VALUES_TO_ECI.set(a, this);
        }
        for (const s of r)
          k.NAME_TO_ECI.set(s, this);
      }
      // CharacterSetECI(value: number /*int*/) {
      //   this(new Int32Array {value})
      // }
      // CharacterSetECI(value: number /*int*/, String... otherEncodingNames) {
      //   this.values = new Int32Array {value}
      //   this.otherEncodingNames = otherEncodingNames
      // }
      // CharacterSetECI(values: Int32Array, String... otherEncodingNames) {
      //   this.values = values
      //   this.otherEncodingNames = otherEncodingNames
      // }
      getValueIdentifier() {
        return this.valueIdentifier;
      }
      getName() {
        return this.name;
      }
      getValue() {
        return this.values[0];
      }
      /**
       * @param value character set ECI value
       * @return {@code CharacterSetECI} representing ECI of given value, or null if it is legal but
       *   unsupported
       * @throws FormatException if ECI value is invalid
       */
      static getCharacterSetECIByValue(e) {
        if (e < 0 || e >= 900)
          throw new U("incorect value");
        const t = k.VALUES_TO_ECI.get(e);
        if (t === void 0)
          throw new U("incorect value");
        return t;
      }
      /**
       * @param name character set ECI encoding name
       * @return CharacterSetECI representing ECI for character encoding, or null if it is legal
       *   but unsupported
       */
      static getCharacterSetECIByName(e) {
        const t = k.NAME_TO_ECI.get(e);
        if (t === void 0)
          throw new U("incorect value");
        return t;
      }
      equals(e) {
        if (!(e instanceof k))
          return !1;
        const t = e;
        return this.getName() === t.getName();
      }
    }
    k.VALUE_IDENTIFIER_TO_ECI = /* @__PURE__ */ new Map(), k.VALUES_TO_ECI = /* @__PURE__ */ new Map(), k.NAME_TO_ECI = /* @__PURE__ */ new Map(), k.Cp437 = new k(de.Cp437, Int32Array.from([0, 2]), "Cp437"), k.ISO8859_1 = new k(de.ISO8859_1, Int32Array.from([1, 3]), "ISO-8859-1", "ISO88591", "ISO8859_1"), k.ISO8859_2 = new k(de.ISO8859_2, 4, "ISO-8859-2", "ISO88592", "ISO8859_2"), k.ISO8859_3 = new k(de.ISO8859_3, 5, "ISO-8859-3", "ISO88593", "ISO8859_3"), k.ISO8859_4 = new k(de.ISO8859_4, 6, "ISO-8859-4", "ISO88594", "ISO8859_4"), k.ISO8859_5 = new k(de.ISO8859_5, 7, "ISO-8859-5", "ISO88595", "ISO8859_5"), k.ISO8859_6 = new k(de.ISO8859_6, 8, "ISO-8859-6", "ISO88596", "ISO8859_6"), k.ISO8859_7 = new k(de.ISO8859_7, 9, "ISO-8859-7", "ISO88597", "ISO8859_7"), k.ISO8859_8 = new k(de.ISO8859_8, 10, "ISO-8859-8", "ISO88598", "ISO8859_8"), k.ISO8859_9 = new k(de.ISO8859_9, 11, "ISO-8859-9", "ISO88599", "ISO8859_9"), k.ISO8859_10 = new k(de.ISO8859_10, 12, "ISO-8859-10", "ISO885910", "ISO8859_10"), k.ISO8859_11 = new k(de.ISO8859_11, 13, "ISO-8859-11", "ISO885911", "ISO8859_11"), k.ISO8859_13 = new k(de.ISO8859_13, 15, "ISO-8859-13", "ISO885913", "ISO8859_13"), k.ISO8859_14 = new k(de.ISO8859_14, 16, "ISO-8859-14", "ISO885914", "ISO8859_14"), k.ISO8859_15 = new k(de.ISO8859_15, 17, "ISO-8859-15", "ISO885915", "ISO8859_15"), k.ISO8859_16 = new k(de.ISO8859_16, 18, "ISO-8859-16", "ISO885916", "ISO8859_16"), k.SJIS = new k(de.SJIS, 20, "SJIS", "Shift_JIS"), k.Cp1250 = new k(de.Cp1250, 21, "Cp1250", "windows-1250"), k.Cp1251 = new k(de.Cp1251, 22, "Cp1251", "windows-1251"), k.Cp1252 = new k(de.Cp1252, 23, "Cp1252", "windows-1252"), k.Cp1256 = new k(de.Cp1256, 24, "Cp1256", "windows-1256"), k.UnicodeBigUnmarked = new k(de.UnicodeBigUnmarked, 25, "UnicodeBigUnmarked", "UTF-16BE", "UnicodeBig"), k.UTF8 = new k(de.UTF8, 26, "UTF8", "UTF-8"), k.ASCII = new k(de.ASCII, Int32Array.from([27, 170]), "ASCII", "US-ASCII"), k.Big5 = new k(de.Big5, 28, "Big5"), k.GB18030 = new k(de.GB18030, 29, "GB18030", "GB2312", "EUC_CN", "GBK"), k.EUC_KR = new k(de.EUC_KR, 30, "EUC_KR", "EUC-KR");
    class Wt extends E {
    }
    Wt.kind = "UnsupportedOperationException";
    class Je {
      /**
       * Decodes some Uint8Array to a string format.
       */
      static decode(e, t) {
        const n = this.encodingName(t);
        return this.customDecoder ? this.customDecoder(e, n) : typeof TextDecoder > "u" || this.shouldDecodeOnFallback(n) ? this.decodeFallback(e, n) : new TextDecoder(n).decode(e);
      }
      /**
       * Checks if the decoding method should use the fallback for decoding
       * once Node TextDecoder doesn't support all encoding formats.
       *
       * @param encodingName
       */
      static shouldDecodeOnFallback(e) {
        return !Je.isBrowser() && e === "ISO-8859-1";
      }
      /**
       * Encodes some string into a Uint8Array.
       */
      static encode(e, t) {
        const n = this.encodingName(t);
        return this.customEncoder ? this.customEncoder(e, n) : typeof TextEncoder > "u" ? this.encodeFallback(e) : new TextEncoder().encode(e);
      }
      static isBrowser() {
        return typeof window < "u" && {}.toString.call(window) === "[object Window]";
      }
      /**
       * Returns the string value from some encoding character set.
       */
      static encodingName(e) {
        return typeof e == "string" ? e : e.getName();
      }
      /**
       * Returns character set from some encoding character set.
       */
      static encodingCharacterSet(e) {
        return e instanceof k ? e : k.getCharacterSetECIByName(e);
      }
      /**
       * Runs a fallback for the native decoding funcion.
       */
      static decodeFallback(e, t) {
        const n = this.encodingCharacterSet(t);
        if (Je.isDecodeFallbackSupported(n)) {
          let r = "";
          for (let i = 0, s = e.length; i < s; i++) {
            let o = e[i].toString(16);
            o.length < 2 && (o = "0" + o), r += "%" + o;
          }
          return decodeURIComponent(r);
        }
        if (n.equals(k.UnicodeBigUnmarked))
          return String.fromCharCode.apply(null, new Uint16Array(e.buffer));
        throw new Wt(`Encoding ${this.encodingName(t)} not supported by fallback.`);
      }
      static isDecodeFallbackSupported(e) {
        return e.equals(k.UTF8) || e.equals(k.ISO8859_1) || e.equals(k.ASCII);
      }
      /**
       * Runs a fallback for the native encoding funcion.
       *
       * @see https://stackoverflow.com/a/17192845/4367683
       */
      static encodeFallback(e) {
        const n = btoa(unescape(encodeURIComponent(e))).split(""), r = [];
        for (let i = 0; i < n.length; i++)
          r.push(n[i].charCodeAt(0));
        return new Uint8Array(r);
      }
    }
    class q {
      // SHIFT_JIS.equalsIgnoreCase(PLATFORM_DEFAULT_ENCODING) ||
      // EUC_JP.equalsIgnoreCase(PLATFORM_DEFAULT_ENCODING);
      static castAsNonUtf8Char(e, t = null) {
        const n = t ? t.getName() : this.ISO88591;
        return Je.decode(new Uint8Array([e]), n);
      }
      /**
       * @param bytes bytes encoding a string, whose encoding should be guessed
       * @param hints decode hints if applicable
       * @return name of guessed encoding; at the moment will only guess one of:
       *  {@link #SHIFT_JIS}, {@link #UTF8}, {@link #ISO88591}, or the platform
       *  default encoding if none of these can possibly be correct
       */
      static guessEncoding(e, t) {
        if (t != null && t.get(xe.CHARACTER_SET) !== void 0)
          return t.get(xe.CHARACTER_SET).toString();
        const n = e.length;
        let r = !0, i = !0, s = !0, o = 0, a = 0, l = 0, c = 0, f = 0, d = 0, C = 0, p = 0, b = 0, y = 0, _ = 0;
        const P = e.length > 3 && e[0] === /*(byte) */
        239 && e[1] === /*(byte) */
        187 && e[2] === /*(byte) */
        191;
        for (let F = 0; F < n && (r || i || s); F++) {
          const L = e[F] & 255;
          s && (o > 0 ? L & 128 ? o-- : s = !1 : L & 128 && (L & 64 ? (o++, L & 32 ? (o++, L & 16 ? (o++, L & 8 ? s = !1 : c++) : l++) : a++) : s = !1)), r && (L > 127 && L < 160 ? r = !1 : L > 159 && (L < 192 || L === 215 || L === 247) && _++), i && (f > 0 ? L < 64 || L === 127 || L > 252 ? i = !1 : f-- : L === 128 || L === 160 || L > 239 ? i = !1 : L > 160 && L < 224 ? (d++, p = 0, C++, C > b && (b = C)) : L > 127 ? (f++, C = 0, p++, p > y && (y = p)) : (C = 0, p = 0));
        }
        return s && o > 0 && (s = !1), i && f > 0 && (i = !1), s && (P || a + l + c > 0) ? q.UTF8 : i && (q.ASSUME_SHIFT_JIS || b >= 3 || y >= 3) ? q.SHIFT_JIS : r && i ? b === 2 && d === 2 || _ * 10 >= n ? q.SHIFT_JIS : q.ISO88591 : r ? q.ISO88591 : i ? q.SHIFT_JIS : s ? q.UTF8 : q.PLATFORM_DEFAULT_ENCODING;
      }
      /**
       *
       * @see https://stackoverflow.com/a/13439711/4367683
       *
       * @param append The new string to append.
       * @param args Argumets values to be formated.
       */
      static format(e, ...t) {
        let n = -1;
        function r(s, o, a, l, c, f) {
          if (s === "%%")
            return "%";
          if (t[++n] === void 0)
            return;
          s = l ? parseInt(l.substr(1)) : void 0;
          let d = c ? parseInt(c.substr(1)) : void 0, C;
          switch (f) {
            case "s":
              C = t[n];
              break;
            case "c":
              C = t[n][0];
              break;
            case "f":
              C = parseFloat(t[n]).toFixed(s);
              break;
            case "p":
              C = parseFloat(t[n]).toPrecision(s);
              break;
            case "e":
              C = parseFloat(t[n]).toExponential(s);
              break;
            case "x":
              C = parseInt(t[n]).toString(d || 16);
              break;
            case "d":
              C = parseFloat(parseInt(t[n], d || 10).toPrecision(s)).toFixed(0);
              break;
          }
          C = typeof C == "object" ? JSON.stringify(C) : (+C).toString(d);
          let p = parseInt(a), b = a && a[0] + "" == "0" ? "0" : " ";
          for (; C.length < p; )
            C = o !== void 0 ? C + b : b + C;
          return C;
        }
        let i = /%(-)?(0?[0-9]+)?([.][0-9]+)?([#][0-9]+)?([scfpexd%])/g;
        return e.replace(i, r);
      }
      /**
       *
       */
      static getBytes(e, t) {
        return Je.encode(e, t);
      }
      /**
       * Returns the charcode at the specified index or at index zero.
       */
      static getCharCode(e, t = 0) {
        return e.charCodeAt(t);
      }
      /**
       * Returns char for given charcode
       */
      static getCharAt(e) {
        return String.fromCharCode(e);
      }
    }
    q.SHIFT_JIS = k.SJIS.getName(), q.GB2312 = "GB2312", q.ISO88591 = k.ISO8859_1.getName(), q.EUC_JP = "EUC_JP", q.UTF8 = k.UTF8.getName(), q.PLATFORM_DEFAULT_ENCODING = q.UTF8, q.ASSUME_SHIFT_JIS = !1;
    class ge {
      constructor(e = "") {
        this.value = e;
      }
      enableDecoding(e) {
        return this.encoding = e, this;
      }
      append(e) {
        return typeof e == "string" ? this.value += e.toString() : this.encoding ? this.value += q.castAsNonUtf8Char(e, this.encoding) : this.value += String.fromCharCode(e), this;
      }
      appendChars(e, t, n) {
        for (let r = t; t < t + n; r++)
          this.append(e[r]);
        return this;
      }
      length() {
        return this.value.length;
      }
      charAt(e) {
        return this.value.charAt(e);
      }
      deleteCharAt(e) {
        this.value = this.value.substr(0, e) + this.value.substring(e + 1);
      }
      setCharAt(e, t) {
        this.value = this.value.substr(0, e) + t + this.value.substr(e + 1);
      }
      substring(e, t) {
        return this.value.substring(e, t);
      }
      /**
       * @note helper method for RSS Expanded
       */
      setLengthToZero() {
        this.value = "";
      }
      toString() {
        return this.value;
      }
      insert(e, t) {
        this.value = this.value.substr(0, e) + t + this.value.substr(e + t.length);
      }
    }
    class Fe {
      /**
       * Creates an empty square {@link BitMatrix}.
       *
       * @param dimension height and width
       */
      // public constructor(dimension: number /*int*/) {
      //   this(dimension, dimension)
      // }
      /**
       * Creates an empty {@link BitMatrix}.
       *
       * @param width bit matrix width
       * @param height bit matrix height
       */
      // public constructor(width: number /*int*/, height: number /*int*/) {
      //   if (width < 1 || height < 1) {
      //     throw new IllegalArgumentException("Both dimensions must be greater than 0")
      //   }
      //   this.width = width
      //   this.height = height
      //   this.rowSize = (width + 31) / 32
      //   bits = new int[rowSize * height];
      // }
      constructor(e, t, n, r) {
        if (this.width = e, this.height = t, this.rowSize = n, this.bits = r, t == null && (t = e), this.height = t, e < 1 || t < 1)
          throw new D("Both dimensions must be greater than 0");
        n == null && (n = Math.floor((e + 31) / 32)), this.rowSize = n, r == null && (this.bits = new Int32Array(this.rowSize * this.height));
      }
      /**
       * Interprets a 2D array of booleans as a {@link BitMatrix}, where "true" means an "on" bit.
       *
       * @function parse
       * @param image bits of the image, as a row-major 2D array. Elements are arrays representing rows
       * @return {@link BitMatrix} representation of image
       */
      static parseFromBooleanArray(e) {
        const t = e.length, n = e[0].length, r = new Fe(n, t);
        for (let i = 0; i < t; i++) {
          const s = e[i];
          for (let o = 0; o < n; o++)
            s[o] && r.set(o, i);
        }
        return r;
      }
      /**
       *
       * @function parse
       * @param stringRepresentation
       * @param setString
       * @param unsetString
       */
      static parseFromString(e, t, n) {
        if (e === null)
          throw new D("stringRepresentation cannot be null");
        const r = new Array(e.length);
        let i = 0, s = 0, o = -1, a = 0, l = 0;
        for (; l < e.length; )
          if (e.charAt(l) === `
` || e.charAt(l) === "\r") {
            if (i > s) {
              if (o === -1)
                o = i - s;
              else if (i - s !== o)
                throw new D("row lengths do not match");
              s = i, a++;
            }
            l++;
          } else if (e.substring(l, l + t.length) === t)
            l += t.length, r[i] = !0, i++;
          else if (e.substring(l, l + n.length) === n)
            l += n.length, r[i] = !1, i++;
          else
            throw new D("illegal character encountered: " + e.substring(l));
        if (i > s) {
          if (o === -1)
            o = i - s;
          else if (i - s !== o)
            throw new D("row lengths do not match");
          a++;
        }
        const c = new Fe(o, a);
        for (let f = 0; f < i; f++)
          r[f] && c.set(Math.floor(f % o), Math.floor(f / o));
        return c;
      }
      /**
       * <p>Gets the requested bit, where true means black.</p>
       *
       * @param x The horizontal component (i.e. which column)
       * @param y The vertical component (i.e. which row)
       * @return value of given bit in matrix
       */
      get(e, t) {
        const n = t * this.rowSize + Math.floor(e / 32);
        return (this.bits[n] >>> (e & 31) & 1) !== 0;
      }
      /**
       * <p>Sets the given bit to true.</p>
       *
       * @param x The horizontal component (i.e. which column)
       * @param y The vertical component (i.e. which row)
       */
      set(e, t) {
        const n = t * this.rowSize + Math.floor(e / 32);
        this.bits[n] |= 1 << (e & 31) & 4294967295;
      }
      unset(e, t) {
        const n = t * this.rowSize + Math.floor(e / 32);
        this.bits[n] &= ~(1 << (e & 31) & 4294967295);
      }
      /**
       * <p>Flips the given bit.</p>
       *
       * @param x The horizontal component (i.e. which column)
       * @param y The vertical component (i.e. which row)
       */
      flip(e, t) {
        const n = t * this.rowSize + Math.floor(e / 32);
        this.bits[n] ^= 1 << (e & 31) & 4294967295;
      }
      /**
       * Exclusive-or (XOR): Flip the bit in this {@code BitMatrix} if the corresponding
       * mask bit is set.
       *
       * @param mask XOR mask
       */
      xor(e) {
        if (this.width !== e.getWidth() || this.height !== e.getHeight() || this.rowSize !== e.getRowSize())
          throw new D("input matrix dimensions do not match");
        const t = new le(Math.floor(this.width / 32) + 1), n = this.rowSize, r = this.bits;
        for (let i = 0, s = this.height; i < s; i++) {
          const o = i * n, a = e.getRow(i, t).getBitArray();
          for (let l = 0; l < n; l++)
            r[o + l] ^= a[l];
        }
      }
      /**
       * Clears all bits (sets to false).
       */
      clear() {
        const e = this.bits, t = e.length;
        for (let n = 0; n < t; n++)
          e[n] = 0;
      }
      /**
       * <p>Sets a square region of the bit matrix to true.</p>
       *
       * @param left The horizontal position to begin at (inclusive)
       * @param top The vertical position to begin at (inclusive)
       * @param width The width of the region
       * @param height The height of the region
       */
      setRegion(e, t, n, r) {
        if (t < 0 || e < 0)
          throw new D("Left and top must be nonnegative");
        if (r < 1 || n < 1)
          throw new D("Height and width must be at least 1");
        const i = e + n, s = t + r;
        if (s > this.height || i > this.width)
          throw new D("The region must fit inside the matrix");
        const o = this.rowSize, a = this.bits;
        for (let l = t; l < s; l++) {
          const c = l * o;
          for (let f = e; f < i; f++)
            a[c + Math.floor(f / 32)] |= 1 << (f & 31) & 4294967295;
        }
      }
      /**
       * A fast method to retrieve one row of data from the matrix as a BitArray.
       *
       * @param y The row to retrieve
       * @param row An optional caller-allocated BitArray, will be allocated if null or too small
       * @return The resulting BitArray - this reference should always be used even when passing
       *         your own row
       */
      getRow(e, t) {
        t == null || t.getSize() < this.width ? t = new le(this.width) : t.clear();
        const n = this.rowSize, r = this.bits, i = e * n;
        for (let s = 0; s < n; s++)
          t.setBulk(s * 32, r[i + s]);
        return t;
      }
      /**
       * @param y row to set
       * @param row {@link BitArray} to copy from
       */
      setRow(e, t) {
        re.arraycopy(t.getBitArray(), 0, this.bits, e * this.rowSize, this.rowSize);
      }
      /**
       * Modifies this {@code BitMatrix} to represent the same but rotated 180 degrees
       */
      rotate180() {
        const e = this.getWidth(), t = this.getHeight();
        let n = new le(e), r = new le(e);
        for (let i = 0, s = Math.floor((t + 1) / 2); i < s; i++)
          n = this.getRow(i, n), r = this.getRow(t - 1 - i, r), n.reverse(), r.reverse(), this.setRow(i, r), this.setRow(t - 1 - i, n);
      }
      /**
       * This is useful in detecting the enclosing rectangle of a 'pure' barcode.
       *
       * @return {@code left,top,width,height} enclosing rectangle of all 1 bits, or null if it is all white
       */
      getEnclosingRectangle() {
        const e = this.width, t = this.height, n = this.rowSize, r = this.bits;
        let i = e, s = t, o = -1, a = -1;
        for (let l = 0; l < t; l++)
          for (let c = 0; c < n; c++) {
            const f = r[l * n + c];
            if (f !== 0) {
              if (l < s && (s = l), l > a && (a = l), c * 32 < i) {
                let d = 0;
                for (; !(f << 31 - d & 4294967295); )
                  d++;
                c * 32 + d < i && (i = c * 32 + d);
              }
              if (c * 32 + 31 > o) {
                let d = 31;
                for (; !(f >>> d); )
                  d--;
                c * 32 + d > o && (o = c * 32 + d);
              }
            }
          }
        return o < i || a < s ? null : Int32Array.from([i, s, o - i + 1, a - s + 1]);
      }
      /**
       * This is useful in detecting a corner of a 'pure' barcode.
       *
       * @return {@code x,y} coordinate of top-left-most 1 bit, or null if it is all white
       */
      getTopLeftOnBit() {
        const e = this.rowSize, t = this.bits;
        let n = 0;
        for (; n < t.length && t[n] === 0; )
          n++;
        if (n === t.length)
          return null;
        const r = n / e;
        let i = n % e * 32;
        const s = t[n];
        let o = 0;
        for (; !(s << 31 - o & 4294967295); )
          o++;
        return i += o, Int32Array.from([i, r]);
      }
      getBottomRightOnBit() {
        const e = this.rowSize, t = this.bits;
        let n = t.length - 1;
        for (; n >= 0 && t[n] === 0; )
          n--;
        if (n < 0)
          return null;
        const r = Math.floor(n / e);
        let i = Math.floor(n % e) * 32;
        const s = t[n];
        let o = 31;
        for (; !(s >>> o); )
          o--;
        return i += o, Int32Array.from([i, r]);
      }
      /**
       * @return The width of the matrix
       */
      getWidth() {
        return this.width;
      }
      /**
       * @return The height of the matrix
       */
      getHeight() {
        return this.height;
      }
      /**
       * @return The row size of the matrix
       */
      getRowSize() {
        return this.rowSize;
      }
      /*@Override*/
      equals(e) {
        if (!(e instanceof Fe))
          return !1;
        const t = e;
        return this.width === t.width && this.height === t.height && this.rowSize === t.rowSize && me.equals(this.bits, t.bits);
      }
      /*@Override*/
      hashCode() {
        let e = this.width;
        return e = 31 * e + this.width, e = 31 * e + this.height, e = 31 * e + this.rowSize, e = 31 * e + me.hashCode(this.bits), e;
      }
      /**
       * @return string representation using "X" for set and " " for unset bits
       */
      /*@Override*/
      // public toString(): string {
      //   return toString(": "X, "  ")
      // }
      /**
       * @param setString representation of a set bit
       * @param unsetString representation of an unset bit
       * @return string representation of entire matrix utilizing given strings
       */
      // public toString(setString: string = "X ", unsetString: string = "  "): string {
      //   return this.buildToString(setString, unsetString, "\n")
      // }
      /**
       * @param setString representation of a set bit
       * @param unsetString representation of an unset bit
       * @param lineSeparator newline character in string representation
       * @return string representation of entire matrix utilizing given strings and line separator
       * @deprecated call {@link #toString(String,String)} only, which uses \n line separator always
       */
      // @Deprecated
      toString(e = "X ", t = "  ", n = `
`) {
        return this.buildToString(e, t, n);
      }
      buildToString(e, t, n) {
        let r = new ge();
        for (let i = 0, s = this.height; i < s; i++) {
          for (let o = 0, a = this.width; o < a; o++)
            r.append(this.get(o, i) ? e : t);
          r.append(n);
        }
        return r.toString();
      }
      /*@Override*/
      clone() {
        return new Fe(this.width, this.height, this.rowSize, this.bits.slice());
      }
    }
    class O extends E {
      static getNotFoundInstance() {
        return new O();
      }
    }
    O.kind = "NotFoundException";
    class ke extends Be {
      constructor(e) {
        super(e), this.luminances = ke.EMPTY, this.buckets = new Int32Array(ke.LUMINANCE_BUCKETS);
      }
      // Applies simple sharpening to the row data to improve performance of the 1D Readers.
      /*@Override*/
      getBlackRow(e, t) {
        const n = this.getLuminanceSource(), r = n.getWidth();
        t == null || t.getSize() < r ? t = new le(r) : t.clear(), this.initArrays(r);
        const i = n.getRow(e, this.luminances), s = this.buckets;
        for (let a = 0; a < r; a++)
          s[(i[a] & 255) >> ke.LUMINANCE_SHIFT]++;
        const o = ke.estimateBlackPoint(s);
        if (r < 3)
          for (let a = 0; a < r; a++)
            (i[a] & 255) < o && t.set(a);
        else {
          let a = i[0] & 255, l = i[1] & 255;
          for (let c = 1; c < r - 1; c++) {
            const f = i[c + 1] & 255;
            (l * 4 - a - f) / 2 < o && t.set(c), a = l, l = f;
          }
        }
        return t;
      }
      // Does not sharpen the data, as this call is intended to only be used by 2D Readers.
      /*@Override*/
      getBlackMatrix() {
        const e = this.getLuminanceSource(), t = e.getWidth(), n = e.getHeight(), r = new Fe(t, n);
        this.initArrays(t);
        const i = this.buckets;
        for (let a = 1; a < 5; a++) {
          const l = Math.floor(n * a / 5), c = e.getRow(l, this.luminances), f = Math.floor(t * 4 / 5);
          for (let d = Math.floor(t / 5); d < f; d++) {
            const C = c[d] & 255;
            i[C >> ke.LUMINANCE_SHIFT]++;
          }
        }
        const s = ke.estimateBlackPoint(i), o = e.getMatrix();
        for (let a = 0; a < n; a++) {
          const l = a * t;
          for (let c = 0; c < t; c++)
            (o[l + c] & 255) < s && r.set(c, a);
        }
        return r;
      }
      /*@Override*/
      createBinarizer(e) {
        return new ke(e);
      }
      initArrays(e) {
        this.luminances.length < e && (this.luminances = new Uint8ClampedArray(e));
        const t = this.buckets;
        for (let n = 0; n < ke.LUMINANCE_BUCKETS; n++)
          t[n] = 0;
      }
      static estimateBlackPoint(e) {
        const t = e.length;
        let n = 0, r = 0, i = 0;
        for (let c = 0; c < t; c++)
          e[c] > i && (r = c, i = e[c]), e[c] > n && (n = e[c]);
        let s = 0, o = 0;
        for (let c = 0; c < t; c++) {
          const f = c - r, d = e[c] * f * f;
          d > o && (s = c, o = d);
        }
        if (r > s) {
          const c = r;
          r = s, s = c;
        }
        if (s - r <= t / 16)
          throw new O();
        let a = s - 1, l = -1;
        for (let c = s - 1; c > r; c--) {
          const f = c - r, d = f * f * (s - c) * (n - e[c]);
          d > l && (a = c, l = d);
        }
        return a << ke.LUMINANCE_SHIFT;
      }
    }
    ke.LUMINANCE_BITS = 5, ke.LUMINANCE_SHIFT = 8 - ke.LUMINANCE_BITS, ke.LUMINANCE_BUCKETS = 1 << ke.LUMINANCE_BITS, ke.EMPTY = Uint8ClampedArray.from([0]);
    class $ extends ke {
      constructor(e) {
        super(e), this.matrix = null;
      }
      /**
       * Calculates the final BitMatrix once for all requests. This could be called once from the
       * constructor instead, but there are some advantages to doing it lazily, such as making
       * profiling easier, and not doing heavy lifting when callers don't expect it.
       */
      /*@Override*/
      getBlackMatrix() {
        if (this.matrix !== null)
          return this.matrix;
        const e = this.getLuminanceSource(), t = e.getWidth(), n = e.getHeight();
        if (t >= $.MINIMUM_DIMENSION && n >= $.MINIMUM_DIMENSION) {
          const r = e.getMatrix();
          let i = t >> $.BLOCK_SIZE_POWER;
          t & $.BLOCK_SIZE_MASK && i++;
          let s = n >> $.BLOCK_SIZE_POWER;
          n & $.BLOCK_SIZE_MASK && s++;
          const o = $.calculateBlackPoints(r, i, s, t, n), a = new Fe(t, n);
          $.calculateThresholdForBlock(r, i, s, t, n, o, a), this.matrix = a;
        } else
          this.matrix = super.getBlackMatrix();
        return this.matrix;
      }
      /*@Override*/
      createBinarizer(e) {
        return new $(e);
      }
      /**
       * For each block in the image, calculate the average black point using a 5x5 grid
       * of the blocks around it. Also handles the corner cases (fractional blocks are computed based
       * on the last pixels in the row/column which are also used in the previous block).
       */
      static calculateThresholdForBlock(e, t, n, r, i, s, o) {
        const a = i - $.BLOCK_SIZE, l = r - $.BLOCK_SIZE;
        for (let c = 0; c < n; c++) {
          let f = c << $.BLOCK_SIZE_POWER;
          f > a && (f = a);
          const d = $.cap(c, 2, n - 3);
          for (let C = 0; C < t; C++) {
            let p = C << $.BLOCK_SIZE_POWER;
            p > l && (p = l);
            const b = $.cap(C, 2, t - 3);
            let y = 0;
            for (let P = -2; P <= 2; P++) {
              const F = s[d + P];
              y += F[b - 2] + F[b - 1] + F[b] + F[b + 1] + F[b + 2];
            }
            const _ = y / 25;
            $.thresholdBlock(e, p, f, _, r, o);
          }
        }
      }
      static cap(e, t, n) {
        return e < t ? t : e > n ? n : e;
      }
      /**
       * Applies a single threshold to a block of pixels.
       */
      static thresholdBlock(e, t, n, r, i, s) {
        for (let o = 0, a = n * i + t; o < $.BLOCK_SIZE; o++, a += i)
          for (let l = 0; l < $.BLOCK_SIZE; l++)
            (e[a + l] & 255) <= r && s.set(t + l, n + o);
      }
      /**
       * Calculates a single black point for each block of pixels and saves it away.
       * See the following thread for a discussion of this algorithm:
       *  http://groups.google.com/group/zxing/browse_thread/thread/d06efa2c35a7ddc0
       */
      static calculateBlackPoints(e, t, n, r, i) {
        const s = i - $.BLOCK_SIZE, o = r - $.BLOCK_SIZE, a = new Array(n);
        for (let l = 0; l < n; l++) {
          a[l] = new Int32Array(t);
          let c = l << $.BLOCK_SIZE_POWER;
          c > s && (c = s);
          for (let f = 0; f < t; f++) {
            let d = f << $.BLOCK_SIZE_POWER;
            d > o && (d = o);
            let C = 0, p = 255, b = 0;
            for (let _ = 0, P = c * r + d; _ < $.BLOCK_SIZE; _++, P += r) {
              for (let F = 0; F < $.BLOCK_SIZE; F++) {
                const L = e[P + F] & 255;
                C += L, L < p && (p = L), L > b && (b = L);
              }
              if (b - p > $.MIN_DYNAMIC_RANGE)
                for (_++, P += r; _ < $.BLOCK_SIZE; _++, P += r)
                  for (let F = 0; F < $.BLOCK_SIZE; F++)
                    C += e[P + F] & 255;
            }
            let y = C >> $.BLOCK_SIZE_POWER * 2;
            if (b - p <= $.MIN_DYNAMIC_RANGE && (y = p / 2, l > 0 && f > 0)) {
              const _ = (a[l - 1][f] + 2 * a[l][f - 1] + a[l - 1][f - 1]) / 4;
              p < _ && (y = _);
            }
            a[l][f] = y;
          }
        }
        return a;
      }
    }
    $.BLOCK_SIZE_POWER = 3, $.BLOCK_SIZE = 1 << $.BLOCK_SIZE_POWER, $.BLOCK_SIZE_MASK = $.BLOCK_SIZE - 1, $.MINIMUM_DIMENSION = $.BLOCK_SIZE * 5, $.MIN_DYNAMIC_RANGE = 24;
    class tn {
      constructor(e, t) {
        this.width = e, this.height = t;
      }
      /**
       * @return The width of the bitmap.
       */
      getWidth() {
        return this.width;
      }
      /**
       * @return The height of the bitmap.
       */
      getHeight() {
        return this.height;
      }
      /**
       * @return Whether this subclass supports cropping.
       */
      isCropSupported() {
        return !1;
      }
      /**
       * Returns a new object with cropped image data. Implementations may keep a reference to the
       * original data rather than a copy. Only callable if isCropSupported() is true.
       *
       * @param left The left coordinate, which must be in [0,getWidth())
       * @param top The top coordinate, which must be in [0,getHeight())
       * @param width The width of the rectangle to crop.
       * @param height The height of the rectangle to crop.
       * @return A cropped version of this object.
       */
      crop(e, t, n, r) {
        throw new Wt("This luminance source does not support cropping.");
      }
      /**
       * @return Whether this subclass supports counter-clockwise rotation.
       */
      isRotateSupported() {
        return !1;
      }
      /**
       * Returns a new object with rotated image data by 90 degrees counterclockwise.
       * Only callable if {@link #isRotateSupported()} is true.
       *
       * @return A rotated version of this object.
       */
      rotateCounterClockwise() {
        throw new Wt("This luminance source does not support rotation by 90 degrees.");
      }
      /**
       * Returns a new object with rotated image data by 45 degrees counterclockwise.
       * Only callable if {@link #isRotateSupported()} is true.
       *
       * @return A rotated version of this object.
       */
      rotateCounterClockwise45() {
        throw new Wt("This luminance source does not support rotation by 45 degrees.");
      }
      /*@Override*/
      toString() {
        const e = new Uint8ClampedArray(this.width);
        let t = new ge();
        for (let n = 0; n < this.height; n++) {
          const r = this.getRow(n, e);
          for (let i = 0; i < this.width; i++) {
            const s = r[i] & 255;
            let o;
            s < 64 ? o = "#" : s < 128 ? o = "+" : s < 192 ? o = "." : o = " ", t.append(o);
          }
          t.append(`
`);
        }
        return t.toString();
      }
    }
    class Tt extends tn {
      constructor(e) {
        super(e.getWidth(), e.getHeight()), this.delegate = e;
      }
      /*@Override*/
      getRow(e, t) {
        const n = this.delegate.getRow(e, t), r = this.getWidth();
        for (let i = 0; i < r; i++)
          n[i] = /*(byte)*/
          255 - (n[i] & 255);
        return n;
      }
      /*@Override*/
      getMatrix() {
        const e = this.delegate.getMatrix(), t = this.getWidth() * this.getHeight(), n = new Uint8ClampedArray(t);
        for (let r = 0; r < t; r++)
          n[r] = /*(byte)*/
          255 - (e[r] & 255);
        return n;
      }
      /*@Override*/
      isCropSupported() {
        return this.delegate.isCropSupported();
      }
      /*@Override*/
      crop(e, t, n, r) {
        return new Tt(this.delegate.crop(e, t, n, r));
      }
      /*@Override*/
      isRotateSupported() {
        return this.delegate.isRotateSupported();
      }
      /**
       * @return original delegate {@link LuminanceSource} since invert undoes itself
       */
      /*@Override*/
      invert() {
        return this.delegate;
      }
      /*@Override*/
      rotateCounterClockwise() {
        return new Tt(this.delegate.rotateCounterClockwise());
      }
      /*@Override*/
      rotateCounterClockwise45() {
        return new Tt(this.delegate.rotateCounterClockwise45());
      }
    }
    class Nt extends tn {
      constructor(e) {
        super(e.width, e.height), this.canvas = e, this.tempCanvasElement = null, this.buffer = Nt.makeBufferFromCanvasImageData(e);
      }
      static makeBufferFromCanvasImageData(e) {
        const t = e.getContext("2d").getImageData(0, 0, e.width, e.height);
        return Nt.toGrayscaleBuffer(t.data, e.width, e.height);
      }
      static toGrayscaleBuffer(e, t, n) {
        const r = new Uint8ClampedArray(t * n);
        for (let i = 0, s = 0, o = e.length; i < o; i += 4, s++) {
          let a;
          if (e[i + 3] === 0)
            a = 255;
          else {
            const c = e[i], f = e[i + 1], d = e[i + 2];
            a = 306 * c + 601 * f + 117 * d + 512 >> 10;
          }
          r[s] = a;
        }
        return r;
      }
      getRow(e, t) {
        if (e < 0 || e >= this.getHeight())
          throw new D("Requested row is outside the image: " + e);
        const n = this.getWidth(), r = e * n;
        return t === null ? t = this.buffer.slice(r, r + n) : (t.length < n && (t = new Uint8ClampedArray(n)), t.set(this.buffer.slice(r, r + n))), t;
      }
      getMatrix() {
        return this.buffer;
      }
      isCropSupported() {
        return !0;
      }
      crop(e, t, n, r) {
        return super.crop(e, t, n, r), this;
      }
      /**
       * This is always true, since the image is a gray-scale image.
       *
       * @return true
       */
      isRotateSupported() {
        return !0;
      }
      rotateCounterClockwise() {
        return this.rotate(-90), this;
      }
      rotateCounterClockwise45() {
        return this.rotate(-45), this;
      }
      getTempCanvasElement() {
        if (this.tempCanvasElement === null) {
          const e = this.canvas.ownerDocument.createElement("canvas");
          e.width = this.canvas.width, e.height = this.canvas.height, this.tempCanvasElement = e;
        }
        return this.tempCanvasElement;
      }
      rotate(e) {
        const t = this.getTempCanvasElement(), n = t.getContext("2d"), r = e * Nt.DEGREE_TO_RADIANS, i = this.canvas.width, s = this.canvas.height, o = Math.ceil(Math.abs(Math.cos(r)) * i + Math.abs(Math.sin(r)) * s), a = Math.ceil(Math.abs(Math.sin(r)) * i + Math.abs(Math.cos(r)) * s);
        return t.width = o, t.height = a, n.translate(o / 2, a / 2), n.rotate(r), n.drawImage(this.canvas, i / -2, s / -2), this.buffer = Nt.makeBufferFromCanvasImageData(t), this;
      }
      invert() {
        return new Tt(this);
      }
    }
    Nt.DEGREE_TO_RADIANS = Math.PI / 180;
    class sr {
      /**
       * Creates an instance of VideoInputDevice.
       *
       * @param {string} deviceId the video input device id
       * @param {string} label the label of the device if available
       */
      constructor(e, t, n) {
        this.deviceId = e, this.label = t, this.kind = "videoinput", this.groupId = n || void 0;
      }
      /** @inheritdoc */
      toJSON() {
        return {
          kind: this.kind,
          groupId: this.groupId,
          deviceId: this.deviceId,
          label: this.label
        };
      }
    }
    var Ke = (globalThis || gn || self || window || void 0) && (globalThis || gn || self || window || void 0).__awaiter || function(u, e, t, n) {
      function r(i) {
        return i instanceof t ? i : new t(function(s) {
          s(i);
        });
      }
      return new (t || (t = Promise))(function(i, s) {
        function o(c) {
          try {
            l(n.next(c));
          } catch (f) {
            s(f);
          }
        }
        function a(c) {
          try {
            l(n.throw(c));
          } catch (f) {
            s(f);
          }
        }
        function l(c) {
          c.done ? i(c.value) : r(c.value).then(o, a);
        }
        l((n = n.apply(u, e || [])).next());
      });
    };
    class Lt {
      /**
       * Creates an instance of BrowserCodeReader.
       * @param {Reader} reader The reader instance to decode the barcode
       * @param {number} [timeBetweenScansMillis=500] the time delay between subsequent successful decode tries
       *
       * @memberOf BrowserCodeReader
       */
      constructor(e, t = 500, n) {
        this.reader = e, this.timeBetweenScansMillis = t, this._hints = n, this._stopContinuousDecode = !1, this._stopAsyncDecode = !1, this._timeBetweenDecodingAttempts = 0;
      }
      /**
       * If navigator is present.
       */
      get hasNavigator() {
        return typeof navigator < "u";
      }
      /**
       * If mediaDevices under navigator is supported.
       */
      get isMediaDevicesSuported() {
        return this.hasNavigator && !!navigator.mediaDevices;
      }
      /**
       * If enumerateDevices under navigator is supported.
       */
      get canEnumerateDevices() {
        return !!(this.isMediaDevicesSuported && navigator.mediaDevices.enumerateDevices);
      }
      /** Time between two decoding tries in milli seconds. */
      get timeBetweenDecodingAttempts() {
        return this._timeBetweenDecodingAttempts;
      }
      /**
       * Change the time span the decoder waits between two decoding tries.
       *
       * @param {number} millis Time between two decoding tries in milli seconds.
       */
      set timeBetweenDecodingAttempts(e) {
        this._timeBetweenDecodingAttempts = e < 0 ? 0 : e;
      }
      /**
       * Sets the hints.
       */
      set hints(e) {
        this._hints = e || null;
      }
      /**
       * Sets the hints.
       */
      get hints() {
        return this._hints;
      }
      /**
       * Lists all the available video input devices.
       */
      listVideoInputDevices() {
        return Ke(this, void 0, void 0, function* () {
          if (!this.hasNavigator)
            throw new Error("Can't enumerate devices, navigator is not present.");
          if (!this.canEnumerateDevices)
            throw new Error("Can't enumerate devices, method not supported.");
          const e = yield navigator.mediaDevices.enumerateDevices(), t = [];
          for (const n of e) {
            const r = n.kind === "video" ? "videoinput" : n.kind;
            if (r !== "videoinput")
              continue;
            const i = n.deviceId || n.id, s = n.label || `Video device ${t.length + 1}`, o = n.groupId, a = { deviceId: i, label: s, kind: r, groupId: o };
            t.push(a);
          }
          return t;
        });
      }
      /**
       * Obtain the list of available devices with type 'videoinput'.
       *
       * @returns {Promise<VideoInputDevice[]>} an array of available video input devices
       *
       * @memberOf BrowserCodeReader
       *
       * @deprecated Use `listVideoInputDevices` instead.
       */
      getVideoInputDevices() {
        return Ke(this, void 0, void 0, function* () {
          return (yield this.listVideoInputDevices()).map((t) => new sr(t.deviceId, t.label));
        });
      }
      /**
       * Let's you find a device using it's Id.
       */
      findDeviceById(e) {
        return Ke(this, void 0, void 0, function* () {
          const t = yield this.listVideoInputDevices();
          return t ? t.find((n) => n.deviceId === e) : null;
        });
      }
      /**
       * Decodes the barcode from the device specified by deviceId while showing the video in the specified video element.
       *
       * @param deviceId the id of one of the devices obtained after calling getVideoInputDevices. Can be undefined, in this case it will decode from one of the available devices, preffering the main camera (environment facing) if available.
       * @param video the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns The decoding result.
       *
       * @memberOf BrowserCodeReader
       *
       * @deprecated Use `decodeOnceFromVideoDevice` instead.
       */
      decodeFromInputVideoDevice(e, t) {
        return Ke(this, void 0, void 0, function* () {
          return yield this.decodeOnceFromVideoDevice(e, t);
        });
      }
      /**
       * In one attempt, tries to decode the barcode from the device specified by deviceId while showing the video in the specified video element.
       *
       * @param deviceId the id of one of the devices obtained after calling getVideoInputDevices. Can be undefined, in this case it will decode from one of the available devices, preffering the main camera (environment facing) if available.
       * @param video the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeOnceFromVideoDevice(e, t) {
        return Ke(this, void 0, void 0, function* () {
          this.reset();
          let n;
          e ? n = { deviceId: { exact: e } } : n = { facingMode: "environment" };
          const r = { video: n };
          return yield this.decodeOnceFromConstraints(r, t);
        });
      }
      /**
       * In one attempt, tries to decode the barcode from a stream obtained from the given constraints while showing the video in the specified video element.
       *
       * @param constraints the media stream constraints to get s valid media stream to decode from
       * @param video the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeOnceFromConstraints(e, t) {
        return Ke(this, void 0, void 0, function* () {
          const n = yield navigator.mediaDevices.getUserMedia(e);
          return yield this.decodeOnceFromStream(n, t);
        });
      }
      /**
       * In one attempt, tries to decode the barcode from a stream obtained from the given constraints while showing the video in the specified video element.
       *
       * @param {MediaStream} [constraints] the media stream constraints to get s valid media stream to decode from
       * @param {string|HTMLVideoElement} [video] the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns {Promise<Result>} The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeOnceFromStream(e, t) {
        return Ke(this, void 0, void 0, function* () {
          this.reset();
          const n = yield this.attachStreamToVideo(e, t);
          return yield this.decodeOnce(n);
        });
      }
      /**
       * Continuously decodes the barcode from the device specified by device while showing the video in the specified video element.
       *
       * @param {string|null} [deviceId] the id of one of the devices obtained after calling getVideoInputDevices. Can be undefined, in this case it will decode from one of the available devices, preffering the main camera (environment facing) if available.
       * @param {string|HTMLVideoElement|null} [video] the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns {Promise<void>}
       *
       * @memberOf BrowserCodeReader
       *
       * @deprecated Use `decodeFromVideoDevice` instead.
       */
      decodeFromInputVideoDeviceContinuously(e, t, n) {
        return Ke(this, void 0, void 0, function* () {
          return yield this.decodeFromVideoDevice(e, t, n);
        });
      }
      /**
       * Continuously tries to decode the barcode from the device specified by device while showing the video in the specified video element.
       *
       * @param {string|null} [deviceId] the id of one of the devices obtained after calling getVideoInputDevices. Can be undefined, in this case it will decode from one of the available devices, preffering the main camera (environment facing) if available.
       * @param {string|HTMLVideoElement|null} [video] the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns {Promise<void>}
       *
       * @memberOf BrowserCodeReader
       */
      decodeFromVideoDevice(e, t, n) {
        return Ke(this, void 0, void 0, function* () {
          let r;
          e ? r = { deviceId: { exact: e } } : r = { facingMode: "environment" };
          const i = { video: r };
          return yield this.decodeFromConstraints(i, t, n);
        });
      }
      /**
       * Continuously tries to decode the barcode from a stream obtained from the given constraints while showing the video in the specified video element.
       *
       * @param {MediaStream} [constraints] the media stream constraints to get s valid media stream to decode from
       * @param {string|HTMLVideoElement} [video] the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns {Promise<Result>} The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeFromConstraints(e, t, n) {
        return Ke(this, void 0, void 0, function* () {
          const r = yield navigator.mediaDevices.getUserMedia(e);
          return yield this.decodeFromStream(r, t, n);
        });
      }
      /**
       * In one attempt, tries to decode the barcode from a stream obtained from the given constraints while showing the video in the specified video element.
       *
       * @param {MediaStream} [constraints] the media stream constraints to get s valid media stream to decode from
       * @param {string|HTMLVideoElement} [video] the video element in page where to show the video while decoding. Can be either an element id or directly an HTMLVideoElement. Can be undefined, in which case no video will be shown.
       * @returns {Promise<Result>} The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeFromStream(e, t, n) {
        return Ke(this, void 0, void 0, function* () {
          this.reset();
          const r = yield this.attachStreamToVideo(e, t);
          return yield this.decodeContinuously(r, n);
        });
      }
      /**
       * Breaks the decoding loop.
       */
      stopAsyncDecode() {
        this._stopAsyncDecode = !0;
      }
      /**
       * Breaks the decoding loop.
       */
      stopContinuousDecode() {
        this._stopContinuousDecode = !0;
      }
      /**
       * Sets the new stream and request a new decoding-with-delay.
       *
       * @param stream The stream to be shown in the video element.
       * @param decodeFn A callback for the decode method.
       */
      attachStreamToVideo(e, t) {
        return Ke(this, void 0, void 0, function* () {
          const n = this.prepareVideoElement(t);
          return this.addVideoSource(n, e), this.videoElement = n, this.stream = e, yield this.playVideoOnLoadAsync(n), n;
        });
      }
      /**
       *
       * @param videoElement
       */
      playVideoOnLoadAsync(e) {
        return new Promise((t, n) => this.playVideoOnLoad(e, () => t()));
      }
      /**
       * Binds listeners and callbacks to the videoElement.
       *
       * @param element
       * @param callbackFn
       */
      playVideoOnLoad(e, t) {
        this.videoEndedListener = () => this.stopStreams(), this.videoCanPlayListener = () => this.tryPlayVideo(e), e.addEventListener("ended", this.videoEndedListener), e.addEventListener("canplay", this.videoCanPlayListener), e.addEventListener("playing", t), this.tryPlayVideo(e);
      }
      /**
       * Checks if the given video element is currently playing.
       */
      isVideoPlaying(e) {
        return e.currentTime > 0 && !e.paused && !e.ended && e.readyState > 2;
      }
      /**
       * Just tries to play the video and logs any errors.
       * The play call is only made is the video is not already playing.
       */
      tryPlayVideo(e) {
        return Ke(this, void 0, void 0, function* () {
          if (this.isVideoPlaying(e)) {
            console.warn("Trying to play video that is already playing.");
            return;
          }
          try {
            yield e.play();
          } catch {
            console.warn("It was not possible to play the video.");
          }
        });
      }
      /**
       * Searches and validates a media element.
       */
      getMediaElement(e, t) {
        const n = document.getElementById(e);
        if (!n)
          throw new R(`element with id '${e}' not found`);
        if (n.nodeName.toLowerCase() !== t.toLowerCase())
          throw new R(`element with id '${e}' must be an ${t} element`);
        return n;
      }
      /**
       * Decodes the barcode from an image.
       *
       * @param {(string|HTMLImageElement)} [source] The image element that can be either an element id or the element itself. Can be undefined in which case the decoding will be done from the imageUrl parameter.
       * @param {string} [url]
       * @returns {Promise<Result>} The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeFromImage(e, t) {
        if (!e && !t)
          throw new R("either imageElement with a src set or an url must be provided");
        return t && !e ? this.decodeFromImageUrl(t) : this.decodeFromImageElement(e);
      }
      /**
       * Decodes the barcode from a video.
       *
       * @param {(string|HTMLImageElement)} [source] The image element that can be either an element id or the element itself. Can be undefined in which case the decoding will be done from the imageUrl parameter.
       * @param {string} [url]
       * @returns {Promise<Result>} The decoding result.
       *
       * @memberOf BrowserCodeReader
       */
      decodeFromVideo(e, t) {
        if (!e && !t)
          throw new R("Either an element with a src set or an URL must be provided");
        return t && !e ? this.decodeFromVideoUrl(t) : this.decodeFromVideoElement(e);
      }
      /**
       * Decodes continuously the barcode from a video.
       *
       * @param {(string|HTMLImageElement)} [source] The image element that can be either an element id or the element itself. Can be undefined in which case the decoding will be done from the imageUrl parameter.
       * @param {string} [url]
       * @returns {Promise<Result>} The decoding result.
       *
       * @memberOf BrowserCodeReader
       *
       * @experimental
       */
      decodeFromVideoContinuously(e, t, n) {
        if (e === void 0 && t === void 0)
          throw new R("Either an element with a src set or an URL must be provided");
        return t && !e ? this.decodeFromVideoUrlContinuously(t, n) : this.decodeFromVideoElementContinuously(e, n);
      }
      /**
       * Decodes something from an image HTML element.
       */
      decodeFromImageElement(e) {
        if (!e)
          throw new R("An image element must be provided.");
        this.reset();
        const t = this.prepareImageElement(e);
        this.imageElement = t;
        let n;
        return this.isImageLoaded(t) ? n = this.decodeOnce(t, !1, !0) : n = this._decodeOnLoadImage(t), n;
      }
      /**
       * Decodes something from an image HTML element.
       */
      decodeFromVideoElement(e) {
        const t = this._decodeFromVideoElementSetup(e);
        return this._decodeOnLoadVideo(t);
      }
      /**
       * Decodes something from an image HTML element.
       */
      decodeFromVideoElementContinuously(e, t) {
        const n = this._decodeFromVideoElementSetup(e);
        return this._decodeOnLoadVideoContinuously(n, t);
      }
      /**
       * Sets up the video source so it can be decoded when loaded.
       *
       * @param source The video source element.
       */
      _decodeFromVideoElementSetup(e) {
        if (!e)
          throw new R("A video element must be provided.");
        this.reset();
        const t = this.prepareVideoElement(e);
        return this.videoElement = t, t;
      }
      /**
       * Decodes an image from a URL.
       */
      decodeFromImageUrl(e) {
        if (!e)
          throw new R("An URL must be provided.");
        this.reset();
        const t = this.prepareImageElement();
        this.imageElement = t;
        const n = this._decodeOnLoadImage(t);
        return t.src = e, n;
      }
      /**
       * Decodes an image from a URL.
       */
      decodeFromVideoUrl(e) {
        if (!e)
          throw new R("An URL must be provided.");
        this.reset();
        const t = this.prepareVideoElement(), n = this.decodeFromVideoElement(t);
        return t.src = e, n;
      }
      /**
       * Decodes an image from a URL.
       *
       * @experimental
       */
      decodeFromVideoUrlContinuously(e, t) {
        if (!e)
          throw new R("An URL must be provided.");
        this.reset();
        const n = this.prepareVideoElement(), r = this.decodeFromVideoElementContinuously(n, t);
        return n.src = e, r;
      }
      _decodeOnLoadImage(e) {
        return new Promise((t, n) => {
          this.imageLoadedListener = () => this.decodeOnce(e, !1, !0).then(t, n), e.addEventListener("load", this.imageLoadedListener);
        });
      }
      _decodeOnLoadVideo(e) {
        return Ke(this, void 0, void 0, function* () {
          return yield this.playVideoOnLoadAsync(e), yield this.decodeOnce(e);
        });
      }
      _decodeOnLoadVideoContinuously(e, t) {
        return Ke(this, void 0, void 0, function* () {
          yield this.playVideoOnLoadAsync(e), this.decodeContinuously(e, t);
        });
      }
      isImageLoaded(e) {
        return !(!e.complete || e.naturalWidth === 0);
      }
      prepareImageElement(e) {
        let t;
        return typeof e > "u" && (t = document.createElement("img"), t.width = 200, t.height = 200), typeof e == "string" && (t = this.getMediaElement(e, "img")), e instanceof HTMLImageElement && (t = e), t;
      }
      /**
       * Sets a HTMLVideoElement for scanning or creates a new one.
       *
       * @param videoSource The HTMLVideoElement to be set.
       */
      prepareVideoElement(e) {
        let t;
        return !e && typeof document < "u" && (t = document.createElement("video"), t.width = 200, t.height = 200), typeof e == "string" && (t = this.getMediaElement(e, "video")), e instanceof HTMLVideoElement && (t = e), t.setAttribute("autoplay", "true"), t.setAttribute("muted", "true"), t.setAttribute("playsinline", "true"), t;
      }
      /**
       * Tries to decode from the video input until it finds some value.
       */
      decodeOnce(e, t = !0, n = !0) {
        this._stopAsyncDecode = !1;
        const r = (i, s) => {
          if (this._stopAsyncDecode) {
            s(new O("Video stream has ended before any code could be detected.")), this._stopAsyncDecode = void 0;
            return;
          }
          try {
            const o = this.decode(e);
            i(o);
          } catch (o) {
            const a = t && o instanceof O, c = (o instanceof J || o instanceof U) && n;
            if (a || c)
              return setTimeout(r, this._timeBetweenDecodingAttempts, i, s);
            s(o);
          }
        };
        return new Promise((i, s) => r(i, s));
      }
      /**
       * Continuously decodes from video input.
       */
      decodeContinuously(e, t) {
        this._stopContinuousDecode = !1;
        const n = () => {
          if (this._stopContinuousDecode) {
            this._stopContinuousDecode = void 0;
            return;
          }
          try {
            const r = this.decode(e);
            t(r, null), setTimeout(n, this.timeBetweenScansMillis);
          } catch (r) {
            t(null, r);
            const i = r instanceof J || r instanceof U, s = r instanceof O;
            (i || s) && setTimeout(n, this._timeBetweenDecodingAttempts);
          }
        };
        n();
      }
      /**
       * Gets the BinaryBitmap for ya! (and decodes it)
       */
      decode(e) {
        const t = this.createBinaryBitmap(e);
        return this.decodeBitmap(t);
      }
      /**
       * Returns true if media element is indeed a {@link HtmlVideoElement}.
       */
      _isHTMLVideoElement(e) {
        return e.videoWidth !== 0;
      }
      /**
       * Overwriting this allows you to manipulate the next frame in anyway
       * you want before decode.
       */
      drawFrameOnCanvas(e, t, n) {
        t || (t = {
          sx: 0,
          sy: 0,
          sWidth: e.videoWidth,
          sHeight: e.videoHeight,
          dx: 0,
          dy: 0,
          dWidth: e.videoWidth,
          dHeight: e.videoHeight
        }), n || (n = this.captureCanvasContext), n.drawImage(
          e,
          t.sx,
          t.sy,
          t.sWidth,
          t.sHeight,
          t.dx,
          t.dy,
          t.dWidth,
          t.dHeight
        );
      }
      /**
       * Ovewriting this allows you to manipulate the snapshot image in anyway
       *  you want before decode.
       */
      drawImageOnCanvas(e, t, n = this.captureCanvasContext) {
        t || (t = {
          sx: 0,
          sy: 0,
          sWidth: e.naturalWidth,
          sHeight: e.naturalHeight,
          dx: 0,
          dy: 0,
          dWidth: e.naturalWidth,
          dHeight: e.naturalHeight
        }), n || (n = this.captureCanvasContext), n.drawImage(
          e,
          t.sx,
          t.sy,
          t.sWidth,
          t.sHeight,
          t.dx,
          t.dy,
          t.dWidth,
          t.dHeight
        );
      }
      /**
       * Creates a binaryBitmap based in some image source.
       *
       * @param mediaElement HTML element containing drawable image source.
       */
      createBinaryBitmap(e) {
        this.getCaptureCanvasContext(e), this._isHTMLVideoElement(e) ? this.drawFrameOnCanvas(e) : this.drawImageOnCanvas(e);
        const t = this.getCaptureCanvas(e), n = new Nt(t), r = new $(n);
        return new ne(r);
      }
      getCaptureCanvasContext(e) {
        if (!this.captureCanvasContext) {
          const n = this.getCaptureCanvas(e).getContext("2d");
          this.captureCanvasContext = n;
        }
        return this.captureCanvasContext;
      }
      getCaptureCanvas(e) {
        if (!this.captureCanvas) {
          const t = this.createCaptureCanvas(e);
          this.captureCanvas = t;
        }
        return this.captureCanvas;
      }
      /**
       * Call the encapsulated readers decode
       */
      decodeBitmap(e) {
        return this.reader.decode(e, this._hints);
      }
      /**
       * 🖌 Prepares the canvas for capture and scan frames.
       */
      createCaptureCanvas(e) {
        if (typeof document > "u")
          return this._destroyCaptureCanvas(), null;
        const t = document.createElement("canvas");
        let n, r;
        return typeof e < "u" && (e instanceof HTMLVideoElement ? (n = e.videoWidth, r = e.videoHeight) : e instanceof HTMLImageElement && (n = e.naturalWidth || e.width, r = e.naturalHeight || e.height)), t.style.width = n + "px", t.style.height = r + "px", t.width = n, t.height = r, t;
      }
      /**
       * Stops the continuous scan and cleans the stream.
       */
      stopStreams() {
        this.stream && (this.stream.getVideoTracks().forEach((e) => e.stop()), this.stream = void 0), this._stopAsyncDecode === !1 && this.stopAsyncDecode(), this._stopContinuousDecode === !1 && this.stopContinuousDecode();
      }
      /**
       * Resets the code reader to the initial state. Cancels any ongoing barcode scanning from video or camera.
       *
       * @memberOf BrowserCodeReader
       */
      reset() {
        this.stopStreams(), this._destroyVideoElement(), this._destroyImageElement(), this._destroyCaptureCanvas();
      }
      _destroyVideoElement() {
        this.videoElement && (typeof this.videoEndedListener < "u" && this.videoElement.removeEventListener("ended", this.videoEndedListener), typeof this.videoPlayingEventListener < "u" && this.videoElement.removeEventListener("playing", this.videoPlayingEventListener), typeof this.videoCanPlayListener < "u" && this.videoElement.removeEventListener("loadedmetadata", this.videoCanPlayListener), this.cleanVideoSource(this.videoElement), this.videoElement = void 0);
      }
      _destroyImageElement() {
        this.imageElement && (this.imageLoadedListener !== void 0 && this.imageElement.removeEventListener("load", this.imageLoadedListener), this.imageElement.src = void 0, this.imageElement.removeAttribute("src"), this.imageElement = void 0);
      }
      /**
       * Cleans canvas references 🖌
       */
      _destroyCaptureCanvas() {
        this.captureCanvasContext = void 0, this.captureCanvas = void 0;
      }
      /**
       * Defines what the videoElement src will be.
       *
       * @param videoElement
       * @param stream
       */
      addVideoSource(e, t) {
        try {
          e.srcObject = t;
        } catch {
          e.src = URL.createObjectURL(t);
        }
      }
      /**
       * Unbinds a HTML video src property.
       *
       * @param videoElement
       */
      cleanVideoSource(e) {
        try {
          e.srcObject = null;
        } catch {
          e.src = "";
        }
        this.videoElement.removeAttribute("src");
      }
    }
    class je {
      // public constructor(private text: string,
      //               Uint8Array rawBytes,
      //               ResultPoconst resultPoints: Int32Array,
      //               BarcodeFormat format) {
      //   this(text, rawBytes, resultPoints, format, System.currentTimeMillis())
      // }
      // public constructor(text: string,
      //               Uint8Array rawBytes,
      //               ResultPoconst resultPoints: Int32Array,
      //               BarcodeFormat format,
      //               long timestamp) {
      //   this(text, rawBytes, rawBytes == null ? 0 : 8 * rawBytes.length,
      //        resultPoints, format, timestamp)
      // }
      constructor(e, t, n = t == null ? 0 : 8 * t.length, r, i, s = re.currentTimeMillis()) {
        this.text = e, this.rawBytes = t, this.numBits = n, this.resultPoints = r, this.format = i, this.timestamp = s, this.text = e, this.rawBytes = t, n == null ? this.numBits = t == null ? 0 : 8 * t.length : this.numBits = n, this.resultPoints = r, this.format = i, this.resultMetadata = null, s == null ? this.timestamp = re.currentTimeMillis() : this.timestamp = s;
      }
      /**
       * @return raw text encoded by the barcode
       */
      getText() {
        return this.text;
      }
      /**
       * @return raw bytes encoded by the barcode, if applicable, otherwise {@code null}
       */
      getRawBytes() {
        return this.rawBytes;
      }
      /**
       * @return how many bits of {@link #getRawBytes()} are valid; typically 8 times its length
       * @since 3.3.0
       */
      getNumBits() {
        return this.numBits;
      }
      /**
       * @return points related to the barcode in the image. These are typically points
       *         identifying finder patterns or the corners of the barcode. The exact meaning is
       *         specific to the type of barcode that was decoded.
       */
      getResultPoints() {
        return this.resultPoints;
      }
      /**
       * @return {@link BarcodeFormat} representing the format of the barcode that was decoded
       */
      getBarcodeFormat() {
        return this.format;
      }
      /**
       * @return {@link Map} mapping {@link ResultMetadataType} keys to values. May be
       *   {@code null}. This contains optional metadata about what was detected about the barcode,
       *   like orientation.
       */
      getResultMetadata() {
        return this.resultMetadata;
      }
      putMetadata(e, t) {
        this.resultMetadata === null && (this.resultMetadata = /* @__PURE__ */ new Map()), this.resultMetadata.set(e, t);
      }
      putAllMetadata(e) {
        e !== null && (this.resultMetadata === null ? this.resultMetadata = e : this.resultMetadata = new Map(e));
      }
      addResultPoints(e) {
        const t = this.resultPoints;
        if (t === null)
          this.resultPoints = e;
        else if (e !== null && e.length > 0) {
          const n = new Array(t.length + e.length);
          re.arraycopy(t, 0, n, 0, t.length), re.arraycopy(e, 0, n, t.length, e.length), this.resultPoints = n;
        }
      }
      getTimestamp() {
        return this.timestamp;
      }
      /*@Override*/
      toString() {
        return this.text;
      }
    }
    var vn;
    (function(u) {
      u[u.AZTEC = 0] = "AZTEC", u[u.CODABAR = 1] = "CODABAR", u[u.CODE_39 = 2] = "CODE_39", u[u.CODE_93 = 3] = "CODE_93", u[u.CODE_128 = 4] = "CODE_128", u[u.DATA_MATRIX = 5] = "DATA_MATRIX", u[u.EAN_8 = 6] = "EAN_8", u[u.EAN_13 = 7] = "EAN_13", u[u.ITF = 8] = "ITF", u[u.MAXICODE = 9] = "MAXICODE", u[u.PDF_417 = 10] = "PDF_417", u[u.QR_CODE = 11] = "QR_CODE", u[u.RSS_14 = 12] = "RSS_14", u[u.RSS_EXPANDED = 13] = "RSS_EXPANDED", u[u.UPC_A = 14] = "UPC_A", u[u.UPC_E = 15] = "UPC_E", u[u.UPC_EAN_EXTENSION = 16] = "UPC_EAN_EXTENSION";
    })(vn || (vn = {}));
    var z = vn, Ln;
    (function(u) {
      u[u.OTHER = 0] = "OTHER", u[u.ORIENTATION = 1] = "ORIENTATION", u[u.BYTE_SEGMENTS = 2] = "BYTE_SEGMENTS", u[u.ERROR_CORRECTION_LEVEL = 3] = "ERROR_CORRECTION_LEVEL", u[u.ISSUE_NUMBER = 4] = "ISSUE_NUMBER", u[u.SUGGESTED_PRICE = 5] = "SUGGESTED_PRICE", u[u.POSSIBLE_COUNTRY = 6] = "POSSIBLE_COUNTRY", u[u.UPC_EAN_EXTENSION = 7] = "UPC_EAN_EXTENSION", u[u.PDF417_EXTRA_METADATA = 8] = "PDF417_EXTRA_METADATA", u[u.STRUCTURED_APPEND_SEQUENCE = 9] = "STRUCTURED_APPEND_SEQUENCE", u[u.STRUCTURED_APPEND_PARITY = 10] = "STRUCTURED_APPEND_PARITY";
    })(Ln || (Ln = {}));
    var Ue = Ln;
    class nn {
      // public constructor(rawBytes: Uint8Array,
      //                      text: string,
      //                      List<Uint8Array> byteSegments,
      //                      String ecLevel) {
      //   this(rawBytes, text, byteSegments, ecLevel, -1, -1)
      // }
      constructor(e, t, n, r, i = -1, s = -1) {
        this.rawBytes = e, this.text = t, this.byteSegments = n, this.ecLevel = r, this.structuredAppendSequenceNumber = i, this.structuredAppendParity = s, this.numBits = e == null ? 0 : 8 * e.length;
      }
      /**
       * @return raw bytes representing the result, or {@code null} if not applicable
       */
      getRawBytes() {
        return this.rawBytes;
      }
      /**
       * @return how many bits of {@link #getRawBytes()} are valid; typically 8 times its length
       * @since 3.3.0
       */
      getNumBits() {
        return this.numBits;
      }
      /**
       * @param numBits overrides the number of bits that are valid in {@link #getRawBytes()}
       * @since 3.3.0
       */
      setNumBits(e) {
        this.numBits = e;
      }
      /**
       * @return text representation of the result
       */
      getText() {
        return this.text;
      }
      /**
       * @return list of byte segments in the result, or {@code null} if not applicable
       */
      getByteSegments() {
        return this.byteSegments;
      }
      /**
       * @return name of error correction level used, or {@code null} if not applicable
       */
      getECLevel() {
        return this.ecLevel;
      }
      /**
       * @return number of errors corrected, or {@code null} if not applicable
       */
      getErrorsCorrected() {
        return this.errorsCorrected;
      }
      setErrorsCorrected(e) {
        this.errorsCorrected = e;
      }
      /**
       * @return number of erasures corrected, or {@code null} if not applicable
       */
      getErasures() {
        return this.erasures;
      }
      setErasures(e) {
        this.erasures = e;
      }
      /**
       * @return arbitrary additional metadata
       */
      getOther() {
        return this.other;
      }
      setOther(e) {
        this.other = e;
      }
      hasStructuredAppend() {
        return this.structuredAppendParity >= 0 && this.structuredAppendSequenceNumber >= 0;
      }
      getStructuredAppendParity() {
        return this.structuredAppendParity;
      }
      getStructuredAppendSequenceNumber() {
        return this.structuredAppendSequenceNumber;
      }
    }
    class rn {
      /**
       * @return 2 to the power of a in GF(size)
       */
      exp(e) {
        return this.expTable[e];
      }
      /**
       * @return base 2 log of a in GF(size)
       */
      log(e) {
        if (e === 0)
          throw new D();
        return this.logTable[e];
      }
      /**
       * Implements both addition and subtraction -- they are the same in GF(size).
       *
       * @return sum/difference of a and b
       */
      static addOrSubtract(e, t) {
        return e ^ t;
      }
    }
    class $e {
      /**
       * @param field the {@link GenericGF} instance representing the field to use
       * to perform computations
       * @param coefficients coefficients as ints representing elements of GF(size), arranged
       * from most significant (highest-power term) coefficient to least significant
       * @throws IllegalArgumentException if argument is null or empty,
       * or if leading coefficient is 0 and this is not a
       * constant polynomial (that is, it is not the monomial "0")
       */
      constructor(e, t) {
        if (t.length === 0)
          throw new D();
        this.field = e;
        const n = t.length;
        if (n > 1 && t[0] === 0) {
          let r = 1;
          for (; r < n && t[r] === 0; )
            r++;
          r === n ? this.coefficients = Int32Array.from([0]) : (this.coefficients = new Int32Array(n - r), re.arraycopy(t, r, this.coefficients, 0, this.coefficients.length));
        } else
          this.coefficients = t;
      }
      getCoefficients() {
        return this.coefficients;
      }
      /**
       * @return degree of this polynomial
       */
      getDegree() {
        return this.coefficients.length - 1;
      }
      /**
       * @return true iff this polynomial is the monomial "0"
       */
      isZero() {
        return this.coefficients[0] === 0;
      }
      /**
       * @return coefficient of x^degree term in this polynomial
       */
      getCoefficient(e) {
        return this.coefficients[this.coefficients.length - 1 - e];
      }
      /**
       * @return evaluation of this polynomial at a given point
       */
      evaluateAt(e) {
        if (e === 0)
          return this.getCoefficient(0);
        const t = this.coefficients;
        let n;
        if (e === 1) {
          n = 0;
          for (let s = 0, o = t.length; s !== o; s++) {
            const a = t[s];
            n = rn.addOrSubtract(n, a);
          }
          return n;
        }
        n = t[0];
        const r = t.length, i = this.field;
        for (let s = 1; s < r; s++)
          n = rn.addOrSubtract(i.multiply(e, n), t[s]);
        return n;
      }
      addOrSubtract(e) {
        if (!this.field.equals(e.field))
          throw new D("GenericGFPolys do not have same GenericGF field");
        if (this.isZero())
          return e;
        if (e.isZero())
          return this;
        let t = this.coefficients, n = e.coefficients;
        if (t.length > n.length) {
          const s = t;
          t = n, n = s;
        }
        let r = new Int32Array(n.length);
        const i = n.length - t.length;
        re.arraycopy(n, 0, r, 0, i);
        for (let s = i; s < n.length; s++)
          r[s] = rn.addOrSubtract(t[s - i], n[s]);
        return new $e(this.field, r);
      }
      multiply(e) {
        if (!this.field.equals(e.field))
          throw new D("GenericGFPolys do not have same GenericGF field");
        if (this.isZero() || e.isZero())
          return this.field.getZero();
        const t = this.coefficients, n = t.length, r = e.coefficients, i = r.length, s = new Int32Array(n + i - 1), o = this.field;
        for (let a = 0; a < n; a++) {
          const l = t[a];
          for (let c = 0; c < i; c++)
            s[a + c] = rn.addOrSubtract(s[a + c], o.multiply(l, r[c]));
        }
        return new $e(o, s);
      }
      multiplyScalar(e) {
        if (e === 0)
          return this.field.getZero();
        if (e === 1)
          return this;
        const t = this.coefficients.length, n = this.field, r = new Int32Array(t), i = this.coefficients;
        for (let s = 0; s < t; s++)
          r[s] = n.multiply(i[s], e);
        return new $e(n, r);
      }
      multiplyByMonomial(e, t) {
        if (e < 0)
          throw new D();
        if (t === 0)
          return this.field.getZero();
        const n = this.coefficients, r = n.length, i = new Int32Array(r + e), s = this.field;
        for (let o = 0; o < r; o++)
          i[o] = s.multiply(n[o], t);
        return new $e(s, i);
      }
      divide(e) {
        if (!this.field.equals(e.field))
          throw new D("GenericGFPolys do not have same GenericGF field");
        if (e.isZero())
          throw new D("Divide by 0");
        const t = this.field;
        let n = t.getZero(), r = this;
        const i = e.getCoefficient(e.getDegree()), s = t.inverse(i);
        for (; r.getDegree() >= e.getDegree() && !r.isZero(); ) {
          const o = r.getDegree() - e.getDegree(), a = t.multiply(r.getCoefficient(r.getDegree()), s), l = e.multiplyByMonomial(o, a), c = t.buildMonomial(o, a);
          n = n.addOrSubtract(c), r = r.addOrSubtract(l);
        }
        return [n, r];
      }
      /*@Override*/
      toString() {
        let e = "";
        for (let t = this.getDegree(); t >= 0; t--) {
          let n = this.getCoefficient(t);
          if (n !== 0) {
            if (n < 0 ? (e += " - ", n = -n) : e.length > 0 && (e += " + "), t === 0 || n !== 1) {
              const r = this.field.log(n);
              r === 0 ? e += "1" : r === 1 ? e += "a" : (e += "a^", e += r);
            }
            t !== 0 && (t === 1 ? e += "x" : (e += "x^", e += t));
          }
        }
        return e;
      }
    }
    class An extends E {
    }
    An.kind = "ArithmeticException";
    class ce extends rn {
      /**
       * Create a representation of GF(size) using the given primitive polynomial.
       *
       * @param primitive irreducible polynomial whose coefficients are represented by
       *  the bits of an int, where the least-significant bit represents the constant
       *  coefficient
       * @param size the size of the field
       * @param b the factor b in the generator polynomial can be 0- or 1-based
       *  (g(x) = (x+a^b)(x+a^(b+1))...(x+a^(b+2t-1))).
       *  In most cases it should be 1, but for QR code it is 0.
       */
      constructor(e, t, n) {
        super(), this.primitive = e, this.size = t, this.generatorBase = n;
        const r = new Int32Array(t);
        let i = 1;
        for (let o = 0; o < t; o++)
          r[o] = i, i *= 2, i >= t && (i ^= e, i &= t - 1);
        this.expTable = r;
        const s = new Int32Array(t);
        for (let o = 0; o < t - 1; o++)
          s[r[o]] = o;
        this.logTable = s, this.zero = new $e(this, Int32Array.from([0])), this.one = new $e(this, Int32Array.from([1]));
      }
      getZero() {
        return this.zero;
      }
      getOne() {
        return this.one;
      }
      /**
       * @return the monomial representing coefficient * x^degree
       */
      buildMonomial(e, t) {
        if (e < 0)
          throw new D();
        if (t === 0)
          return this.zero;
        const n = new Int32Array(e + 1);
        return n[0] = t, new $e(this, n);
      }
      /**
       * @return multiplicative inverse of a
       */
      inverse(e) {
        if (e === 0)
          throw new An();
        return this.expTable[this.size - this.logTable[e] - 1];
      }
      /**
       * @return product of a and b in GF(size)
       */
      multiply(e, t) {
        return e === 0 || t === 0 ? 0 : this.expTable[(this.logTable[e] + this.logTable[t]) % (this.size - 1)];
      }
      getSize() {
        return this.size;
      }
      getGeneratorBase() {
        return this.generatorBase;
      }
      /*@Override*/
      toString() {
        return "GF(0x" + j.toHexString(this.primitive) + "," + this.size + ")";
      }
      equals(e) {
        return e === this;
      }
    }
    ce.AZTEC_DATA_12 = new ce(4201, 4096, 1), ce.AZTEC_DATA_10 = new ce(1033, 1024, 1), ce.AZTEC_DATA_6 = new ce(67, 64, 1), ce.AZTEC_PARAM = new ce(19, 16, 1), ce.QR_CODE_FIELD_256 = new ce(285, 256, 0), ce.DATA_MATRIX_FIELD_256 = new ce(301, 256, 1), ce.AZTEC_DATA_8 = ce.DATA_MATRIX_FIELD_256, ce.MAXICODE_FIELD_64 = ce.AZTEC_DATA_6;
    class Xt extends E {
    }
    Xt.kind = "ReedSolomonException";
    class gt extends E {
    }
    gt.kind = "IllegalStateException";
    class sn {
      constructor(e) {
        this.field = e;
      }
      /**
       * <p>Decodes given set of received codewords, which include both data and error-correction
       * codewords. Really, this means it uses Reed-Solomon to detect and correct errors, in-place,
       * in the input.</p>
       *
       * @param received data and error-correction codewords
       * @param twoS number of error-correction codewords available
       * @throws ReedSolomonException if decoding fails for any reason
       */
      decode(e, t) {
        const n = this.field, r = new $e(n, e), i = new Int32Array(t);
        let s = !0;
        for (let C = 0; C < t; C++) {
          const p = r.evaluateAt(n.exp(C + n.getGeneratorBase()));
          i[i.length - 1 - C] = p, p !== 0 && (s = !1);
        }
        if (s)
          return;
        const o = new $e(n, i), a = this.runEuclideanAlgorithm(n.buildMonomial(t, 1), o, t), l = a[0], c = a[1], f = this.findErrorLocations(l), d = this.findErrorMagnitudes(c, f);
        for (let C = 0; C < f.length; C++) {
          const p = e.length - 1 - n.log(f[C]);
          if (p < 0)
            throw new Xt("Bad error location");
          e[p] = ce.addOrSubtract(e[p], d[C]);
        }
      }
      runEuclideanAlgorithm(e, t, n) {
        if (e.getDegree() < t.getDegree()) {
          const C = e;
          e = t, t = C;
        }
        const r = this.field;
        let i = e, s = t, o = r.getZero(), a = r.getOne();
        for (; s.getDegree() >= (n / 2 | 0); ) {
          let C = i, p = o;
          if (i = s, o = a, i.isZero())
            throw new Xt("r_{i-1} was zero");
          s = C;
          let b = r.getZero();
          const y = i.getCoefficient(i.getDegree()), _ = r.inverse(y);
          for (; s.getDegree() >= i.getDegree() && !s.isZero(); ) {
            const P = s.getDegree() - i.getDegree(), F = r.multiply(s.getCoefficient(s.getDegree()), _);
            b = b.addOrSubtract(r.buildMonomial(P, F)), s = s.addOrSubtract(i.multiplyByMonomial(P, F));
          }
          if (a = b.multiply(o).addOrSubtract(p), s.getDegree() >= i.getDegree())
            throw new gt("Division algorithm failed to reduce polynomial?");
        }
        const l = a.getCoefficient(0);
        if (l === 0)
          throw new Xt("sigmaTilde(0) was zero");
        const c = r.inverse(l), f = a.multiplyScalar(c), d = s.multiplyScalar(c);
        return [f, d];
      }
      findErrorLocations(e) {
        const t = e.getDegree();
        if (t === 1)
          return Int32Array.from([e.getCoefficient(1)]);
        const n = new Int32Array(t);
        let r = 0;
        const i = this.field;
        for (let s = 1; s < i.getSize() && r < t; s++)
          e.evaluateAt(s) === 0 && (n[r] = i.inverse(s), r++);
        if (r !== t)
          throw new Xt("Error locator degree does not match number of roots");
        return n;
      }
      findErrorMagnitudes(e, t) {
        const n = t.length, r = new Int32Array(n), i = this.field;
        for (let s = 0; s < n; s++) {
          const o = i.inverse(t[s]);
          let a = 1;
          for (let l = 0; l < n; l++)
            if (s !== l) {
              const c = i.multiply(t[l], o), f = c & 1 ? c & -2 : c | 1;
              a = i.multiply(a, f);
            }
          r[s] = i.multiply(e.evaluateAt(o), i.inverse(a)), i.getGeneratorBase() !== 0 && (r[s] = i.multiply(r[s], o));
        }
        return r;
      }
    }
    var He;
    (function(u) {
      u[u.UPPER = 0] = "UPPER", u[u.LOWER = 1] = "LOWER", u[u.MIXED = 2] = "MIXED", u[u.DIGIT = 3] = "DIGIT", u[u.PUNCT = 4] = "PUNCT", u[u.BINARY = 5] = "BINARY";
    })(He || (He = {}));
    class Ie {
      decode(e) {
        this.ddata = e;
        let t = e.getBits(), n = this.extractBits(t), r = this.correctBits(n), i = Ie.convertBoolArrayToByteArray(r), s = Ie.getEncodedData(r), o = new nn(i, s, null, null);
        return o.setNumBits(r.length), o;
      }
      // This method is used for testing the high-level encoder
      static highLevelDecode(e) {
        return this.getEncodedData(e);
      }
      /**
       * Gets the string encoded in the aztec code bits
       *
       * @return the decoded string
       */
      static getEncodedData(e) {
        let t = e.length, n = He.UPPER, r = He.UPPER, i = "", s = 0;
        for (; s < t; )
          if (r === He.BINARY) {
            if (t - s < 5)
              break;
            let o = Ie.readCode(e, s, 5);
            if (s += 5, o === 0) {
              if (t - s < 11)
                break;
              o = Ie.readCode(e, s, 11) + 31, s += 11;
            }
            for (let a = 0; a < o; a++) {
              if (t - s < 8) {
                s = t;
                break;
              }
              const l = Ie.readCode(e, s, 8);
              i += /*(char)*/
              q.castAsNonUtf8Char(l), s += 8;
            }
            r = n;
          } else {
            let o = r === He.DIGIT ? 4 : 5;
            if (t - s < o)
              break;
            let a = Ie.readCode(e, s, o);
            s += o;
            let l = Ie.getCharacter(r, a);
            l.startsWith("CTRL_") ? (n = r, r = Ie.getTable(l.charAt(5)), l.charAt(6) === "L" && (n = r)) : (i += l, r = n);
          }
        return i;
      }
      /**
       * gets the table corresponding to the char passed
       */
      static getTable(e) {
        switch (e) {
          case "L":
            return He.LOWER;
          case "P":
            return He.PUNCT;
          case "M":
            return He.MIXED;
          case "D":
            return He.DIGIT;
          case "B":
            return He.BINARY;
          case "U":
          default:
            return He.UPPER;
        }
      }
      /**
       * Gets the character (or string) corresponding to the passed code in the given table
       *
       * @param table the table used
       * @param code the code of the character
       */
      static getCharacter(e, t) {
        switch (e) {
          case He.UPPER:
            return Ie.UPPER_TABLE[t];
          case He.LOWER:
            return Ie.LOWER_TABLE[t];
          case He.MIXED:
            return Ie.MIXED_TABLE[t];
          case He.PUNCT:
            return Ie.PUNCT_TABLE[t];
          case He.DIGIT:
            return Ie.DIGIT_TABLE[t];
          default:
            throw new gt("Bad table");
        }
      }
      /**
       * <p>Performs RS error correction on an array of bits.</p>
       *
       * @return the corrected array
       * @throws FormatException if the input contains too many errors
       */
      correctBits(e) {
        let t, n;
        this.ddata.getNbLayers() <= 2 ? (n = 6, t = ce.AZTEC_DATA_6) : this.ddata.getNbLayers() <= 8 ? (n = 8, t = ce.AZTEC_DATA_8) : this.ddata.getNbLayers() <= 22 ? (n = 10, t = ce.AZTEC_DATA_10) : (n = 12, t = ce.AZTEC_DATA_12);
        let r = this.ddata.getNbDatablocks(), i = e.length / n;
        if (i < r)
          throw new U();
        let s = e.length % n, o = new Int32Array(i);
        for (let d = 0; d < i; d++, s += n)
          o[d] = Ie.readCode(e, s, n);
        try {
          new sn(t).decode(o, i - r);
        } catch (d) {
          throw new U(d);
        }
        let a = (1 << n) - 1, l = 0;
        for (let d = 0; d < r; d++) {
          let C = o[d];
          if (C === 0 || C === a)
            throw new U();
          (C === 1 || C === a - 1) && l++;
        }
        let c = new Array(r * n - l), f = 0;
        for (let d = 0; d < r; d++) {
          let C = o[d];
          if (C === 1 || C === a - 1)
            c.fill(C > 1, f, f + n - 1), f += n - 1;
          else
            for (let p = n - 1; p >= 0; --p)
              c[f++] = (C & 1 << p) !== 0;
        }
        return c;
      }
      /**
       * Gets the array of bits from an Aztec Code matrix
       *
       * @return the array of bits
       */
      extractBits(e) {
        let t = this.ddata.isCompact(), n = this.ddata.getNbLayers(), r = (t ? 11 : 14) + n * 4, i = new Int32Array(r), s = new Array(this.totalBitsInLayer(n, t));
        if (t)
          for (let o = 0; o < i.length; o++)
            i[o] = o;
        else {
          let o = r + 1 + 2 * j.truncDivision(j.truncDivision(r, 2) - 1, 15), a = r / 2, l = j.truncDivision(o, 2);
          for (let c = 0; c < a; c++) {
            let f = c + j.truncDivision(c, 15);
            i[a - c - 1] = l - f - 1, i[a + c] = l + f + 1;
          }
        }
        for (let o = 0, a = 0; o < n; o++) {
          let l = (n - o) * 4 + (t ? 9 : 12), c = o * 2, f = r - 1 - c;
          for (let d = 0; d < l; d++) {
            let C = d * 2;
            for (let p = 0; p < 2; p++)
              s[a + C + p] = e.get(i[c + p], i[c + d]), s[a + 2 * l + C + p] = e.get(i[c + d], i[f - p]), s[a + 4 * l + C + p] = e.get(i[f - p], i[f - d]), s[a + 6 * l + C + p] = e.get(i[f - d], i[c + p]);
          }
          a += l * 8;
        }
        return s;
      }
      /**
       * Reads a code of given length and at given index in an array of bits
       */
      static readCode(e, t, n) {
        let r = 0;
        for (let i = t; i < t + n; i++)
          r <<= 1, e[i] && (r |= 1);
        return r;
      }
      /**
       * Reads a code of length 8 in an array of bits, padding with zeros
       */
      static readByte(e, t) {
        let n = e.length - t;
        return n >= 8 ? Ie.readCode(e, t, 8) : Ie.readCode(e, t, n) << 8 - n;
      }
      /**
       * Packs a bit array into bytes, most significant bit first
       */
      static convertBoolArrayToByteArray(e) {
        let t = new Uint8Array((e.length + 7) / 8);
        for (let n = 0; n < t.length; n++)
          t[n] = Ie.readByte(e, 8 * n);
        return t;
      }
      totalBitsInLayer(e, t) {
        return ((t ? 88 : 112) + 16 * e) * e;
      }
    }
    Ie.UPPER_TABLE = [
      "CTRL_PS",
      " ",
      "A",
      "B",
      "C",
      "D",
      "E",
      "F",
      "G",
      "H",
      "I",
      "J",
      "K",
      "L",
      "M",
      "N",
      "O",
      "P",
      "Q",
      "R",
      "S",
      "T",
      "U",
      "V",
      "W",
      "X",
      "Y",
      "Z",
      "CTRL_LL",
      "CTRL_ML",
      "CTRL_DL",
      "CTRL_BS"
    ], Ie.LOWER_TABLE = [
      "CTRL_PS",
      " ",
      "a",
      "b",
      "c",
      "d",
      "e",
      "f",
      "g",
      "h",
      "i",
      "j",
      "k",
      "l",
      "m",
      "n",
      "o",
      "p",
      "q",
      "r",
      "s",
      "t",
      "u",
      "v",
      "w",
      "x",
      "y",
      "z",
      "CTRL_US",
      "CTRL_ML",
      "CTRL_DL",
      "CTRL_BS"
    ], Ie.MIXED_TABLE = [
      // Module parse failed: Octal literal in strict mode (50:29)
      // so number string were scaped
      "CTRL_PS",
      " ",
      "\\1",
      "\\2",
      "\\3",
      "\\4",
      "\\5",
      "\\6",
      "\\7",
      "\b",
      "	",
      `
`,
      "\\13",
      "\f",
      "\r",
      "\\33",
      "\\34",
      "\\35",
      "\\36",
      "\\37",
      "@",
      "\\",
      "^",
      "_",
      "`",
      "|",
      "~",
      "\\177",
      "CTRL_LL",
      "CTRL_UL",
      "CTRL_PL",
      "CTRL_BS"
    ], Ie.PUNCT_TABLE = [
      "",
      "\r",
      `\r
`,
      ". ",
      ", ",
      ": ",
      "!",
      '"',
      "#",
      "$",
      "%",
      "&",
      "'",
      "(",
      ")",
      "*",
      "+",
      ",",
      "-",
      ".",
      "/",
      ":",
      ";",
      "<",
      "=",
      ">",
      "?",
      "[",
      "]",
      "{",
      "}",
      "CTRL_UL"
    ], Ie.DIGIT_TABLE = [
      "CTRL_PS",
      " ",
      "0",
      "1",
      "2",
      "3",
      "4",
      "5",
      "6",
      "7",
      "8",
      "9",
      ",",
      ".",
      "CTRL_UL",
      "CTRL_US"
    ];
    class oe {
      constructor() {
      }
      /**
       * Ends up being a bit faster than {@link Math#round(float)}. This merely rounds its
       * argument to the nearest int, where x.5 rounds up to x+1. Semantics of this shortcut
       * differ slightly from {@link Math#round(float)} in that half rounds down for negative
       * values. -2.5 rounds to -3, not -2. For purposes here it makes no difference.
       *
       * @param d real value to round
       * @return nearest {@code int}
       */
      static round(e) {
        return e === NaN ? 0 : e <= Number.MIN_SAFE_INTEGER ? Number.MIN_SAFE_INTEGER : e >= Number.MAX_SAFE_INTEGER ? Number.MAX_SAFE_INTEGER : (
          /*(int) */
          e + (e < 0 ? -0.5 : 0.5) | 0
        );
      }
      // TYPESCRIPTPORT: maybe remove round method and call directly Math.round, it looks like it doesn't make sense for js
      /**
       * @param aX point A x coordinate
       * @param aY point A y coordinate
       * @param bX point B x coordinate
       * @param bY point B y coordinate
       * @return Euclidean distance between points A and B
       */
      static distance(e, t, n, r) {
        const i = e - n, s = t - r;
        return (
          /*(float) */
          Math.sqrt(i * i + s * s)
        );
      }
      /**
       * @param aX point A x coordinate
       * @param aY point A y coordinate
       * @param bX point B x coordinate
       * @param bY point B y coordinate
       * @return Euclidean distance between points A and B
       */
      // public static distance(aX: number /*int*/, aY: number /*int*/, bX: number /*int*/, bY: number /*int*/): float {
      //   const xDiff = aX - bX
      //   const yDiff = aY - bY
      //   return (float) Math.sqrt(xDiff * xDiff + yDiff * yDiff);
      // }
      /**
       * @param array values to sum
       * @return sum of values in array
       */
      static sum(e) {
        let t = 0;
        for (let n = 0, r = e.length; n !== r; n++) {
          const i = e[n];
          t += i;
        }
        return t;
      }
    }
    class En {
      /**
       * SincTS has no difference between int and float, there's all numbers,
       * this is used only to polyfill Java code.
       */
      static floatToIntBits(e) {
        return e;
      }
    }
    En.MAX_VALUE = Number.MAX_SAFE_INTEGER;
    class W {
      constructor(e, t) {
        this.x = e, this.y = t;
      }
      getX() {
        return this.x;
      }
      getY() {
        return this.y;
      }
      /*@Override*/
      equals(e) {
        if (e instanceof W) {
          const t = e;
          return this.x === t.x && this.y === t.y;
        }
        return !1;
      }
      /*@Override*/
      hashCode() {
        return 31 * En.floatToIntBits(this.x) + En.floatToIntBits(this.y);
      }
      /*@Override*/
      toString() {
        return "(" + this.x + "," + this.y + ")";
      }
      /**
       * Orders an array of three ResultPoints in an order [A,B,C] such that AB is less than AC
       * and BC is less than AC, and the angle between BC and BA is less than 180 degrees.
       *
       * @param patterns array of three {@code ResultPoint} to order
       */
      static orderBestPatterns(e) {
        const t = this.distance(e[0], e[1]), n = this.distance(e[1], e[2]), r = this.distance(e[0], e[2]);
        let i, s, o;
        if (n >= t && n >= r ? (s = e[0], i = e[1], o = e[2]) : r >= n && r >= t ? (s = e[1], i = e[0], o = e[2]) : (s = e[2], i = e[0], o = e[1]), this.crossProductZ(i, s, o) < 0) {
          const a = i;
          i = o, o = a;
        }
        e[0] = i, e[1] = s, e[2] = o;
      }
      /**
       * @param pattern1 first pattern
       * @param pattern2 second pattern
       * @return distance between two points
       */
      static distance(e, t) {
        return oe.distance(e.x, e.y, t.x, t.y);
      }
      /**
       * Returns the z component of the cross product between vectors BC and BA.
       */
      static crossProductZ(e, t, n) {
        const r = t.x, i = t.y;
        return (n.x - r) * (e.y - i) - (n.y - i) * (e.x - r);
      }
    }
    class Cn {
      constructor(e, t) {
        this.bits = e, this.points = t;
      }
      getBits() {
        return this.bits;
      }
      getPoints() {
        return this.points;
      }
    }
    class or extends Cn {
      constructor(e, t, n, r, i) {
        super(e, t), this.compact = n, this.nbDatablocks = r, this.nbLayers = i;
      }
      getNbLayers() {
        return this.nbLayers;
      }
      getNbDatablocks() {
        return this.nbDatablocks;
      }
      isCompact() {
        return this.compact;
      }
    }
    class pt {
      // public constructor(private image: BitMatrix) /*throws NotFoundException*/ {
      //   this(image, INIT_SIZE, image.getWidth() / 2, image.getHeight() / 2)
      // }
      /**
       * @param image barcode image to find a rectangle in
       * @param initSize initial size of search area around center
       * @param x x position of search center
       * @param y y position of search center
       * @throws NotFoundException if image is too small to accommodate {@code initSize}
       */
      constructor(e, t, n, r) {
        this.image = e, this.height = e.getHeight(), this.width = e.getWidth(), t == null && (t = pt.INIT_SIZE), n == null && (n = e.getWidth() / 2 | 0), r == null && (r = e.getHeight() / 2 | 0);
        const i = t / 2 | 0;
        if (this.leftInit = n - i, this.rightInit = n + i, this.upInit = r - i, this.downInit = r + i, this.upInit < 0 || this.leftInit < 0 || this.downInit >= this.height || this.rightInit >= this.width)
          throw new O();
      }
      /**
       * <p>
       * Detects a candidate barcode-like rectangular region within an image. It
       * starts around the center of the image, increases the size of the candidate
       * region until it finds a white rectangular region.
       * </p>
       *
       * @return {@link ResultPoint}[] describing the corners of the rectangular
       *         region. The first and last points are opposed on the diagonal, as
       *         are the second and third. The first point will be the topmost
       *         point and the last, the bottommost. The second point will be
       *         leftmost and the third, the rightmost
       * @throws NotFoundException if no Data Matrix Code can be found
       */
      detect() {
        let e = this.leftInit, t = this.rightInit, n = this.upInit, r = this.downInit, i = !1, s = !0, o = !1, a = !1, l = !1, c = !1, f = !1;
        const d = this.width, C = this.height;
        for (; s; ) {
          s = !1;
          let p = !0;
          for (; (p || !a) && t < d; )
            p = this.containsBlackPoint(n, r, t, !1), p ? (t++, s = !0, a = !0) : a || t++;
          if (t >= d) {
            i = !0;
            break;
          }
          let b = !0;
          for (; (b || !l) && r < C; )
            b = this.containsBlackPoint(e, t, r, !0), b ? (r++, s = !0, l = !0) : l || r++;
          if (r >= C) {
            i = !0;
            break;
          }
          let y = !0;
          for (; (y || !c) && e >= 0; )
            y = this.containsBlackPoint(n, r, e, !1), y ? (e--, s = !0, c = !0) : c || e--;
          if (e < 0) {
            i = !0;
            break;
          }
          let _ = !0;
          for (; (_ || !f) && n >= 0; )
            _ = this.containsBlackPoint(e, t, n, !0), _ ? (n--, s = !0, f = !0) : f || n--;
          if (n < 0) {
            i = !0;
            break;
          }
          s && (o = !0);
        }
        if (!i && o) {
          const p = t - e;
          let b = null;
          for (let F = 1; b === null && F < p; F++)
            b = this.getBlackPointOnSegment(e, r - F, e + F, r);
          if (b == null)
            throw new O();
          let y = null;
          for (let F = 1; y === null && F < p; F++)
            y = this.getBlackPointOnSegment(e, n + F, e + F, n);
          if (y == null)
            throw new O();
          let _ = null;
          for (let F = 1; _ === null && F < p; F++)
            _ = this.getBlackPointOnSegment(t, n + F, t - F, n);
          if (_ == null)
            throw new O();
          let P = null;
          for (let F = 1; P === null && F < p; F++)
            P = this.getBlackPointOnSegment(t, r - F, t - F, r);
          if (P == null)
            throw new O();
          return this.centerEdges(P, b, _, y);
        } else
          throw new O();
      }
      getBlackPointOnSegment(e, t, n, r) {
        const i = oe.round(oe.distance(e, t, n, r)), s = (n - e) / i, o = (r - t) / i, a = this.image;
        for (let l = 0; l < i; l++) {
          const c = oe.round(e + l * s), f = oe.round(t + l * o);
          if (a.get(c, f))
            return new W(c, f);
        }
        return null;
      }
      /**
       * recenters the points of a constant distance towards the center
       *
       * @param y bottom most point
       * @param z left most point
       * @param x right most point
       * @param t top most point
       * @return {@link ResultPoint}[] describing the corners of the rectangular
       *         region. The first and last points are opposed on the diagonal, as
       *         are the second and third. The first point will be the topmost
       *         point and the last, the bottommost. The second point will be
       *         leftmost and the third, the rightmost
       */
      centerEdges(e, t, n, r) {
        const i = e.getX(), s = e.getY(), o = t.getX(), a = t.getY(), l = n.getX(), c = n.getY(), f = r.getX(), d = r.getY(), C = pt.CORR;
        return i < this.width / 2 ? [
          new W(f - C, d + C),
          new W(o + C, a + C),
          new W(l - C, c - C),
          new W(i + C, s - C)
        ] : [
          new W(f + C, d + C),
          new W(o + C, a - C),
          new W(l - C, c + C),
          new W(i - C, s - C)
        ];
      }
      /**
       * Determines whether a segment contains a black point
       *
       * @param a          min value of the scanned coordinate
       * @param b          max value of the scanned coordinate
       * @param fixed      value of fixed coordinate
       * @param horizontal set to true if scan must be horizontal, false if vertical
       * @return true if a black point has been found, else false.
       */
      containsBlackPoint(e, t, n, r) {
        const i = this.image;
        if (r) {
          for (let s = e; s <= t; s++)
            if (i.get(s, n))
              return !0;
        } else
          for (let s = e; s <= t; s++)
            if (i.get(n, s))
              return !0;
        return !1;
      }
    }
    pt.INIT_SIZE = 10, pt.CORR = 1;
    class Pn {
      /**
       * <p>Checks a set of points that have been transformed to sample points on an image against
       * the image's dimensions to see if the point are even within the image.</p>
       *
       * <p>This method will actually "nudge" the endpoints back onto the image if they are found to be
       * barely (less than 1 pixel) off the image. This accounts for imperfect detection of finder
       * patterns in an image where the QR Code runs all the way to the image border.</p>
       *
       * <p>For efficiency, the method will check points from either end of the line until one is found
       * to be within the image. Because the set of points are assumed to be linear, this is valid.</p>
       *
       * @param image image into which the points should map
       * @param points actual points in x1,y1,...,xn,yn form
       * @throws NotFoundException if an endpoint is lies outside the image boundaries
       */
      static checkAndNudgePoints(e, t) {
        const n = e.getWidth(), r = e.getHeight();
        let i = !0;
        for (let s = 0; s < t.length && i; s += 2) {
          const o = Math.floor(t[s]), a = Math.floor(t[s + 1]);
          if (o < -1 || o > n || a < -1 || a > r)
            throw new O();
          i = !1, o === -1 ? (t[s] = 0, i = !0) : o === n && (t[s] = n - 1, i = !0), a === -1 ? (t[s + 1] = 0, i = !0) : a === r && (t[s + 1] = r - 1, i = !0);
        }
        i = !0;
        for (let s = t.length - 2; s >= 0 && i; s -= 2) {
          const o = Math.floor(t[s]), a = Math.floor(t[s + 1]);
          if (o < -1 || o > n || a < -1 || a > r)
            throw new O();
          i = !1, o === -1 ? (t[s] = 0, i = !0) : o === n && (t[s] = n - 1, i = !0), a === -1 ? (t[s + 1] = 0, i = !0) : a === r && (t[s + 1] = r - 1, i = !0);
        }
      }
    }
    class ut {
      constructor(e, t, n, r, i, s, o, a, l) {
        this.a11 = e, this.a21 = t, this.a31 = n, this.a12 = r, this.a22 = i, this.a32 = s, this.a13 = o, this.a23 = a, this.a33 = l;
      }
      static quadrilateralToQuadrilateral(e, t, n, r, i, s, o, a, l, c, f, d, C, p, b, y) {
        const _ = ut.quadrilateralToSquare(e, t, n, r, i, s, o, a);
        return ut.squareToQuadrilateral(l, c, f, d, C, p, b, y).times(_);
      }
      transformPoints(e) {
        const t = e.length, n = this.a11, r = this.a12, i = this.a13, s = this.a21, o = this.a22, a = this.a23, l = this.a31, c = this.a32, f = this.a33;
        for (let d = 0; d < t; d += 2) {
          const C = e[d], p = e[d + 1], b = i * C + a * p + f;
          e[d] = (n * C + s * p + l) / b, e[d + 1] = (r * C + o * p + c) / b;
        }
      }
      transformPointsWithValues(e, t) {
        const n = this.a11, r = this.a12, i = this.a13, s = this.a21, o = this.a22, a = this.a23, l = this.a31, c = this.a32, f = this.a33, d = e.length;
        for (let C = 0; C < d; C++) {
          const p = e[C], b = t[C], y = i * p + a * b + f;
          e[C] = (n * p + s * b + l) / y, t[C] = (r * p + o * b + c) / y;
        }
      }
      static squareToQuadrilateral(e, t, n, r, i, s, o, a) {
        const l = e - n + i - o, c = t - r + s - a;
        if (l === 0 && c === 0)
          return new ut(n - e, i - n, e, r - t, s - r, t, 0, 0, 1);
        {
          const f = n - i, d = o - i, C = r - s, p = a - s, b = f * p - d * C, y = (l * p - d * c) / b, _ = (f * c - l * C) / b;
          return new ut(n - e + y * n, o - e + _ * o, e, r - t + y * r, a - t + _ * a, t, y, _, 1);
        }
      }
      static quadrilateralToSquare(e, t, n, r, i, s, o, a) {
        return ut.squareToQuadrilateral(e, t, n, r, i, s, o, a).buildAdjoint();
      }
      buildAdjoint() {
        return new ut(this.a22 * this.a33 - this.a23 * this.a32, this.a23 * this.a31 - this.a21 * this.a33, this.a21 * this.a32 - this.a22 * this.a31, this.a13 * this.a32 - this.a12 * this.a33, this.a11 * this.a33 - this.a13 * this.a31, this.a12 * this.a31 - this.a11 * this.a32, this.a12 * this.a23 - this.a13 * this.a22, this.a13 * this.a21 - this.a11 * this.a23, this.a11 * this.a22 - this.a12 * this.a21);
      }
      times(e) {
        return new ut(this.a11 * e.a11 + this.a21 * e.a12 + this.a31 * e.a13, this.a11 * e.a21 + this.a21 * e.a22 + this.a31 * e.a23, this.a11 * e.a31 + this.a21 * e.a32 + this.a31 * e.a33, this.a12 * e.a11 + this.a22 * e.a12 + this.a32 * e.a13, this.a12 * e.a21 + this.a22 * e.a22 + this.a32 * e.a23, this.a12 * e.a31 + this.a22 * e.a32 + this.a32 * e.a33, this.a13 * e.a11 + this.a23 * e.a12 + this.a33 * e.a13, this.a13 * e.a21 + this.a23 * e.a22 + this.a33 * e.a23, this.a13 * e.a31 + this.a23 * e.a32 + this.a33 * e.a33);
      }
    }
    class ar extends Pn {
      /*@Override*/
      sampleGrid(e, t, n, r, i, s, o, a, l, c, f, d, C, p, b, y, _, P, F) {
        const L = ut.quadrilateralToQuadrilateral(r, i, s, o, a, l, c, f, d, C, p, b, y, _, P, F);
        return this.sampleGridWithTransform(e, t, n, L);
      }
      /*@Override*/
      sampleGridWithTransform(e, t, n, r) {
        if (t <= 0 || n <= 0)
          throw new O();
        const i = new Fe(t, n), s = new Float32Array(2 * t);
        for (let o = 0; o < n; o++) {
          const a = s.length, l = o + 0.5;
          for (let c = 0; c < a; c += 2)
            s[c] = c / 2 + 0.5, s[c + 1] = l;
          r.transformPoints(s), Pn.checkAndNudgePoints(e, s);
          try {
            for (let c = 0; c < a; c += 2)
              e.get(Math.floor(s[c]), Math.floor(s[c + 1])) && i.set(c / 2, o);
          } catch {
            throw new O();
          }
        }
        return i;
      }
    }
    class _t {
      /**
       * Sets the implementation of GridSampler used by the library. One global
       * instance is stored, which may sound problematic. But, the implementation provided
       * ought to be appropriate for the entire platform, and all uses of this library
       * in the whole lifetime of the JVM. For instance, an Android activity can swap in
       * an implementation that takes advantage of native platform libraries.
       *
       * @param newGridSampler The platform-specific object to install.
       */
      static setGridSampler(e) {
        _t.gridSampler = e;
      }
      /**
       * @return the current implementation of GridSampler
       */
      static getInstance() {
        return _t.gridSampler;
      }
    }
    _t.gridSampler = new ar();
    class qe {
      constructor(e, t) {
        this.x = e, this.y = t;
      }
      toResultPoint() {
        return new W(this.getX(), this.getY());
      }
      getX() {
        return this.x;
      }
      getY() {
        return this.y;
      }
    }
    class lr {
      constructor(e) {
        this.EXPECTED_CORNER_BITS = new Int32Array([
          3808,
          476,
          2107,
          1799
        ]), this.image = e;
      }
      detect() {
        return this.detectMirror(!1);
      }
      /**
       * Detects an Aztec Code in an image.
       *
       * @param isMirror if true, image is a mirror-image of original
       * @return {@link AztecDetectorResult} encapsulating results of detecting an Aztec Code
       * @throws NotFoundException if no Aztec Code can be found
       */
      detectMirror(e) {
        let t = this.getMatrixCenter(), n = this.getBullsEyeCorners(t);
        if (e) {
          let s = n[0];
          n[0] = n[2], n[2] = s;
        }
        this.extractParameters(n);
        let r = this.sampleGrid(this.image, n[this.shift % 4], n[(this.shift + 1) % 4], n[(this.shift + 2) % 4], n[(this.shift + 3) % 4]), i = this.getMatrixCornerPoints(n);
        return new or(r, i, this.compact, this.nbDataBlocks, this.nbLayers);
      }
      /**
       * Extracts the number of data layers and data blocks from the layer around the bull's eye.
       *
       * @param bullsEyeCorners the array of bull's eye corners
       * @throws NotFoundException in case of too many errors or invalid parameters
       */
      extractParameters(e) {
        if (!this.isValidPoint(e[0]) || !this.isValidPoint(e[1]) || !this.isValidPoint(e[2]) || !this.isValidPoint(e[3]))
          throw new O();
        let t = 2 * this.nbCenterLayers, n = new Int32Array([
          this.sampleLine(e[0], e[1], t),
          this.sampleLine(e[1], e[2], t),
          this.sampleLine(e[2], e[3], t),
          this.sampleLine(e[3], e[0], t)
          // Top
        ]);
        this.shift = this.getRotation(n, t);
        let r = 0;
        for (let s = 0; s < 4; s++) {
          let o = n[(this.shift + s) % 4];
          this.compact ? (r <<= 7, r += o >> 1 & 127) : (r <<= 10, r += (o >> 2 & 992) + (o >> 1 & 31));
        }
        let i = this.getCorrectedParameterData(r, this.compact);
        this.compact ? (this.nbLayers = (i >> 6) + 1, this.nbDataBlocks = (i & 63) + 1) : (this.nbLayers = (i >> 11) + 1, this.nbDataBlocks = (i & 2047) + 1);
      }
      getRotation(e, t) {
        let n = 0;
        e.forEach((r, i, s) => {
          let o = (r >> t - 2 << 1) + (r & 1);
          n = (n << 3) + o;
        }), n = ((n & 1) << 11) + (n >> 1);
        for (let r = 0; r < 4; r++)
          if (j.bitCount(n ^ this.EXPECTED_CORNER_BITS[r]) <= 2)
            return r;
        throw new O();
      }
      /**
       * Corrects the parameter bits using Reed-Solomon algorithm.
       *
       * @param parameterData parameter bits
       * @param compact true if this is a compact Aztec code
       * @throws NotFoundException if the array contains too many errors
       */
      getCorrectedParameterData(e, t) {
        let n, r;
        t ? (n = 7, r = 2) : (n = 10, r = 4);
        let i = n - r, s = new Int32Array(n);
        for (let a = n - 1; a >= 0; --a)
          s[a] = e & 15, e >>= 4;
        try {
          new sn(ce.AZTEC_PARAM).decode(s, i);
        } catch {
          throw new O();
        }
        let o = 0;
        for (let a = 0; a < r; a++)
          o = (o << 4) + s[a];
        return o;
      }
      /**
       * Finds the corners of a bull-eye centered on the passed point.
       * This returns the centers of the diagonal points just outside the bull's eye
       * Returns [topRight, bottomRight, bottomLeft, topLeft]
       *
       * @param pCenter Center point
       * @return The corners of the bull-eye
       * @throws NotFoundException If no valid bull-eye can be found
       */
      getBullsEyeCorners(e) {
        let t = e, n = e, r = e, i = e, s = !0;
        for (this.nbCenterLayers = 1; this.nbCenterLayers < 9; this.nbCenterLayers++) {
          let f = this.getFirstDifferent(t, s, 1, -1), d = this.getFirstDifferent(n, s, 1, 1), C = this.getFirstDifferent(r, s, -1, 1), p = this.getFirstDifferent(i, s, -1, -1);
          if (this.nbCenterLayers > 2) {
            let b = this.distancePoint(p, f) * this.nbCenterLayers / (this.distancePoint(i, t) * (this.nbCenterLayers + 2));
            if (b < 0.75 || b > 1.25 || !this.isWhiteOrBlackRectangle(f, d, C, p))
              break;
          }
          t = f, n = d, r = C, i = p, s = !s;
        }
        if (this.nbCenterLayers !== 5 && this.nbCenterLayers !== 7)
          throw new O();
        this.compact = this.nbCenterLayers === 5;
        let o = new W(t.getX() + 0.5, t.getY() - 0.5), a = new W(n.getX() + 0.5, n.getY() + 0.5), l = new W(r.getX() - 0.5, r.getY() + 0.5), c = new W(i.getX() - 0.5, i.getY() - 0.5);
        return this.expandSquare([o, a, l, c], 2 * this.nbCenterLayers - 3, 2 * this.nbCenterLayers);
      }
      /**
       * Finds a candidate center point of an Aztec code from an image
       *
       * @return the center point
       */
      getMatrixCenter() {
        let e, t, n, r;
        try {
          let o = new pt(this.image).detect();
          e = o[0], t = o[1], n = o[2], r = o[3];
        } catch {
          let a = this.image.getWidth() / 2, l = this.image.getHeight() / 2;
          e = this.getFirstDifferent(new qe(a + 7, l - 7), !1, 1, -1).toResultPoint(), t = this.getFirstDifferent(new qe(a + 7, l + 7), !1, 1, 1).toResultPoint(), n = this.getFirstDifferent(new qe(a - 7, l + 7), !1, -1, 1).toResultPoint(), r = this.getFirstDifferent(new qe(a - 7, l - 7), !1, -1, -1).toResultPoint();
        }
        let i = oe.round((e.getX() + r.getX() + t.getX() + n.getX()) / 4), s = oe.round((e.getY() + r.getY() + t.getY() + n.getY()) / 4);
        try {
          let o = new pt(this.image, 15, i, s).detect();
          e = o[0], t = o[1], n = o[2], r = o[3];
        } catch {
          e = this.getFirstDifferent(new qe(i + 7, s - 7), !1, 1, -1).toResultPoint(), t = this.getFirstDifferent(new qe(i + 7, s + 7), !1, 1, 1).toResultPoint(), n = this.getFirstDifferent(new qe(i - 7, s + 7), !1, -1, 1).toResultPoint(), r = this.getFirstDifferent(new qe(i - 7, s - 7), !1, -1, -1).toResultPoint();
        }
        return i = oe.round((e.getX() + r.getX() + t.getX() + n.getX()) / 4), s = oe.round((e.getY() + r.getY() + t.getY() + n.getY()) / 4), new qe(i, s);
      }
      /**
       * Gets the Aztec code corners from the bull's eye corners and the parameters.
       *
       * @param bullsEyeCorners the array of bull's eye corners
       * @return the array of aztec code corners
       */
      getMatrixCornerPoints(e) {
        return this.expandSquare(e, 2 * this.nbCenterLayers, this.getDimension());
      }
      /**
       * Creates a BitMatrix by sampling the provided image.
       * topLeft, topRight, bottomRight, and bottomLeft are the centers of the squares on the
       * diagonal just outside the bull's eye.
       */
      sampleGrid(e, t, n, r, i) {
        let s = _t.getInstance(), o = this.getDimension(), a = o / 2 - this.nbCenterLayers, l = o / 2 + this.nbCenterLayers;
        return s.sampleGrid(
          e,
          o,
          o,
          a,
          a,
          // topleft
          l,
          a,
          // topright
          l,
          l,
          // bottomright
          a,
          l,
          // bottomleft
          t.getX(),
          t.getY(),
          n.getX(),
          n.getY(),
          r.getX(),
          r.getY(),
          i.getX(),
          i.getY()
        );
      }
      /**
       * Samples a line.
       *
       * @param p1   start point (inclusive)
       * @param p2   end point (exclusive)
       * @param size number of bits
       * @return the array of bits as an int (first bit is high-order bit of result)
       */
      sampleLine(e, t, n) {
        let r = 0, i = this.distanceResultPoint(e, t), s = i / n, o = e.getX(), a = e.getY(), l = s * (t.getX() - e.getX()) / i, c = s * (t.getY() - e.getY()) / i;
        for (let f = 0; f < n; f++)
          this.image.get(oe.round(o + f * l), oe.round(a + f * c)) && (r |= 1 << n - f - 1);
        return r;
      }
      /**
       * @return true if the border of the rectangle passed in parameter is compound of white points only
       *         or black points only
       */
      isWhiteOrBlackRectangle(e, t, n, r) {
        let i = 3;
        e = new qe(e.getX() - i, e.getY() + i), t = new qe(t.getX() - i, t.getY() - i), n = new qe(n.getX() + i, n.getY() - i), r = new qe(r.getX() + i, r.getY() + i);
        let s = this.getColor(r, e);
        if (s === 0)
          return !1;
        let o = this.getColor(e, t);
        return o !== s || (o = this.getColor(t, n), o !== s) ? !1 : (o = this.getColor(n, r), o === s);
      }
      /**
       * Gets the color of a segment
       *
       * @return 1 if segment more than 90% black, -1 if segment is more than 90% white, 0 else
       */
      getColor(e, t) {
        let n = this.distancePoint(e, t), r = (t.getX() - e.getX()) / n, i = (t.getY() - e.getY()) / n, s = 0, o = e.getX(), a = e.getY(), l = this.image.get(e.getX(), e.getY()), c = Math.ceil(n);
        for (let d = 0; d < c; d++)
          o += r, a += i, this.image.get(oe.round(o), oe.round(a)) !== l && s++;
        let f = s / n;
        return f > 0.1 && f < 0.9 ? 0 : f <= 0.1 === l ? 1 : -1;
      }
      /**
       * Gets the coordinate of the first point with a different color in the given direction
       */
      getFirstDifferent(e, t, n, r) {
        let i = e.getX() + n, s = e.getY() + r;
        for (; this.isValid(i, s) && this.image.get(i, s) === t; )
          i += n, s += r;
        for (i -= n, s -= r; this.isValid(i, s) && this.image.get(i, s) === t; )
          i += n;
        for (i -= n; this.isValid(i, s) && this.image.get(i, s) === t; )
          s += r;
        return s -= r, new qe(i, s);
      }
      /**
       * Expand the square represented by the corner points by pushing out equally in all directions
       *
       * @param cornerPoints the corners of the square, which has the bull's eye at its center
       * @param oldSide the original length of the side of the square in the target bit matrix
       * @param newSide the new length of the size of the square in the target bit matrix
       * @return the corners of the expanded square
       */
      expandSquare(e, t, n) {
        let r = n / (2 * t), i = e[0].getX() - e[2].getX(), s = e[0].getY() - e[2].getY(), o = (e[0].getX() + e[2].getX()) / 2, a = (e[0].getY() + e[2].getY()) / 2, l = new W(o + r * i, a + r * s), c = new W(o - r * i, a - r * s);
        i = e[1].getX() - e[3].getX(), s = e[1].getY() - e[3].getY(), o = (e[1].getX() + e[3].getX()) / 2, a = (e[1].getY() + e[3].getY()) / 2;
        let f = new W(o + r * i, a + r * s), d = new W(o - r * i, a - r * s);
        return [l, f, c, d];
      }
      isValid(e, t) {
        return e >= 0 && e < this.image.getWidth() && t > 0 && t < this.image.getHeight();
      }
      isValidPoint(e) {
        let t = oe.round(e.getX()), n = oe.round(e.getY());
        return this.isValid(t, n);
      }
      distancePoint(e, t) {
        return oe.distance(e.getX(), e.getY(), t.getX(), t.getY());
      }
      distanceResultPoint(e, t) {
        return oe.distance(e.getX(), e.getY(), t.getX(), t.getY());
      }
      getDimension() {
        return this.compact ? 4 * this.nbLayers + 11 : this.nbLayers <= 4 ? 4 * this.nbLayers + 15 : 4 * this.nbLayers + 2 * (j.truncDivision(this.nbLayers - 4, 8) + 1) + 15;
      }
    }
    class mn {
      /**
       * Locates and decodes a Data Matrix code in an image.
       *
       * @return a String representing the content encoded by the Data Matrix code
       * @throws NotFoundException if a Data Matrix code cannot be found
       * @throws FormatException if a Data Matrix code cannot be decoded
       */
      decode(e, t = null) {
        let n = null, r = new lr(e.getBlackMatrix()), i = null, s = null;
        try {
          let c = r.detectMirror(!1);
          i = c.getPoints(), this.reportFoundResultPoints(t, i), s = new Ie().decode(c);
        } catch (c) {
          n = c;
        }
        if (s == null)
          try {
            let c = r.detectMirror(!0);
            i = c.getPoints(), this.reportFoundResultPoints(t, i), s = new Ie().decode(c);
          } catch (c) {
            throw n ?? c;
          }
        let o = new je(s.getText(), s.getRawBytes(), s.getNumBits(), i, z.AZTEC, re.currentTimeMillis()), a = s.getByteSegments();
        a != null && o.putMetadata(Ue.BYTE_SEGMENTS, a);
        let l = s.getECLevel();
        return l != null && o.putMetadata(Ue.ERROR_CORRECTION_LEVEL, l), o;
      }
      reportFoundResultPoints(e, t) {
        if (e != null) {
          let n = e.get(xe.NEED_RESULT_POINT_CALLBACK);
          n != null && t.forEach((r, i, s) => {
            n.foundPossibleResultPoint(r);
          });
        }
      }
      // @Override
      reset() {
      }
    }
    class Hr extends Lt {
      /**
       * Creates an instance of BrowserAztecCodeReader.
       * @param {number} [timeBetweenScansMillis=500] the time delay between subsequent decode tries
       *
       * @memberOf BrowserAztecCodeReader
       */
      constructor(e = 500) {
        super(new mn(), e);
      }
    }
    class Me {
      /*
      @Override
      public Result decode(BinaryBitmap image) throws NotFoundException, FormatException {
        return decode(image, null);
      }
      */
      // Note that we don't try rotation without the try harder flag, even if rotation was supported.
      // @Override
      decode(e, t) {
        try {
          return this.doDecode(e, t);
        } catch {
          if (t && t.get(xe.TRY_HARDER) === !0 && e.isRotateSupported()) {
            const i = e.rotateCounterClockwise(), s = this.doDecode(i, t), o = s.getResultMetadata();
            let a = 270;
            o !== null && o.get(Ue.ORIENTATION) === !0 && (a = a + o.get(Ue.ORIENTATION) % 360), s.putMetadata(Ue.ORIENTATION, a);
            const l = s.getResultPoints();
            if (l !== null) {
              const c = i.getHeight();
              for (let f = 0; f < l.length; f++)
                l[f] = new W(c - l[f].getY() - 1, l[f].getX());
            }
            return s;
          } else
            throw new O();
        }
      }
      // @Override
      reset() {
      }
      /**
       * We're going to examine rows from the middle outward, searching alternately above and below the
       * middle, and farther out each time. rowStep is the number of rows between each successive
       * attempt above and below the middle. So we'd scan row middle, then middle - rowStep, then
       * middle + rowStep, then middle - (2 * rowStep), etc.
       * rowStep is bigger as the image is taller, but is always at least 1. We've somewhat arbitrarily
       * decided that moving up and down by about 1/16 of the image is pretty good; we try more of the
       * image if "trying harder".
       *
       * @param image The image to decode
       * @param hints Any hints that were requested
       * @return The contents of the decoded barcode
       * @throws NotFoundException Any spontaneous errors which occur
       */
      doDecode(e, t) {
        const n = e.getWidth(), r = e.getHeight();
        let i = new le(n);
        const s = t && t.get(xe.TRY_HARDER) === !0, o = Math.max(1, r >> (s ? 8 : 5));
        let a;
        s ? a = r : a = 15;
        const l = Math.trunc(r / 2);
        for (let c = 0; c < a; c++) {
          const f = Math.trunc((c + 1) / 2), d = (c & 1) === 0, C = l + o * (d ? f : -f);
          if (C < 0 || C >= r)
            break;
          try {
            i = e.getBlackRow(C, i);
          } catch {
            continue;
          }
          for (let p = 0; p < 2; p++) {
            if (p === 1 && (i.reverse(), t && t.get(xe.NEED_RESULT_POINT_CALLBACK) === !0)) {
              const b = /* @__PURE__ */ new Map();
              t.forEach((y, _) => b.set(_, y)), b.delete(xe.NEED_RESULT_POINT_CALLBACK), t = b;
            }
            try {
              const b = this.decodeRow(C, i, t);
              if (p === 1) {
                b.putMetadata(Ue.ORIENTATION, 180);
                const y = b.getResultPoints();
                y !== null && (y[0] = new W(n - y[0].getX() - 1, y[0].getY()), y[1] = new W(n - y[1].getX() - 1, y[1].getY()));
              }
              return b;
            } catch {
            }
          }
        }
        throw new O();
      }
      /**
       * Records the size of successive runs of white and black pixels in a row, starting at a given point.
       * The values are recorded in the given array, and the number of runs recorded is equal to the size
       * of the array. If the row starts on a white pixel at the given start point, then the first count
       * recorded is the run of white pixels starting from that point; likewise it is the count of a run
       * of black pixels if the row begin on a black pixels at that point.
       *
       * @param row row to count from
       * @param start offset into row to start at
       * @param counters array into which to record counts
       * @throws NotFoundException if counters cannot be filled entirely from row before running out
       *  of pixels
       */
      static recordPattern(e, t, n) {
        const r = n.length;
        for (let l = 0; l < r; l++)
          n[l] = 0;
        const i = e.getSize();
        if (t >= i)
          throw new O();
        let s = !e.get(t), o = 0, a = t;
        for (; a < i; ) {
          if (e.get(a) !== s)
            n[o]++;
          else {
            if (++o === r)
              break;
            n[o] = 1, s = !s;
          }
          a++;
        }
        if (!(o === r || o === r - 1 && a === i))
          throw new O();
      }
      static recordPatternInReverse(e, t, n) {
        let r = n.length, i = e.get(t);
        for (; t > 0 && r >= 0; )
          e.get(--t) !== i && (r--, i = !i);
        if (r >= 0)
          throw new O();
        Me.recordPattern(e, t + 1, n);
      }
      /**
       * Determines how closely a set of observed counts of runs of black/white values matches a given
       * target pattern. This is reported as the ratio of the total variance from the expected pattern
       * proportions across all pattern elements, to the length of the pattern.
       *
       * @param counters observed counters
       * @param pattern expected pattern
       * @param maxIndividualVariance The most any counter can differ before we give up
       * @return ratio of total variance between counters and pattern compared to total pattern size
       */
      static patternMatchVariance(e, t, n) {
        const r = e.length;
        let i = 0, s = 0;
        for (let l = 0; l < r; l++)
          i += e[l], s += t[l];
        if (i < s)
          return Number.POSITIVE_INFINITY;
        const o = i / s;
        n *= o;
        let a = 0;
        for (let l = 0; l < r; l++) {
          const c = e[l], f = t[l] * o, d = c > f ? c - f : f - c;
          if (d > n)
            return Number.POSITIVE_INFINITY;
          a += d;
        }
        return a / i;
      }
    }
    class V extends Me {
      static findStartPattern(e) {
        const t = e.getSize(), n = e.getNextSet(0);
        let r = 0, i = Int32Array.from([0, 0, 0, 0, 0, 0]), s = n, o = !1;
        const a = 6;
        for (let l = n; l < t; l++)
          if (e.get(l) !== o)
            i[r]++;
          else {
            if (r === a - 1) {
              let c = V.MAX_AVG_VARIANCE, f = -1;
              for (let d = V.CODE_START_A; d <= V.CODE_START_C; d++) {
                const C = Me.patternMatchVariance(i, V.CODE_PATTERNS[d], V.MAX_INDIVIDUAL_VARIANCE);
                C < c && (c = C, f = d);
              }
              if (f >= 0 && e.isRange(Math.max(0, s - (l - s) / 2), s, !1))
                return Int32Array.from([s, l, f]);
              s += i[0] + i[1], i = i.slice(2, i.length - 1), i[r - 1] = 0, i[r] = 0, r--;
            } else
              r++;
            i[r] = 1, o = !o;
          }
        throw new O();
      }
      static decodeCode(e, t, n) {
        Me.recordPattern(e, n, t);
        let r = V.MAX_AVG_VARIANCE, i = -1;
        for (let s = 0; s < V.CODE_PATTERNS.length; s++) {
          const o = V.CODE_PATTERNS[s], a = this.patternMatchVariance(t, o, V.MAX_INDIVIDUAL_VARIANCE);
          a < r && (r = a, i = s);
        }
        if (i >= 0)
          return i;
        throw new O();
      }
      decodeRow(e, t, n) {
        const r = n && n.get(xe.ASSUME_GS1) === !0, i = V.findStartPattern(t), s = i[2];
        let o = 0;
        const a = new Uint8Array(20);
        a[o++] = s;
        let l;
        switch (s) {
          case V.CODE_START_A:
            l = V.CODE_CODE_A;
            break;
          case V.CODE_START_B:
            l = V.CODE_CODE_B;
            break;
          case V.CODE_START_C:
            l = V.CODE_CODE_C;
            break;
          default:
            throw new U();
        }
        let c = !1, f = !1, d = "", C = i[0], p = i[1];
        const b = Int32Array.from([0, 0, 0, 0, 0, 0]);
        let y = 0, _ = 0, P = s, F = 0, L = !0, ie = !1, Q = !1;
        for (; !c; ) {
          const $t = f;
          switch (f = !1, y = _, _ = V.decodeCode(t, b, p), a[o++] = _, _ !== V.CODE_STOP && (L = !0), _ !== V.CODE_STOP && (F++, P += F * _), C = p, p += b.reduce((Ai, Ei) => Ai + Ei, 0), _) {
            case V.CODE_START_A:
            case V.CODE_START_B:
            case V.CODE_START_C:
              throw new U();
          }
          switch (l) {
            case V.CODE_CODE_A:
              if (_ < 64)
                Q === ie ? d += String.fromCharCode(32 + _) : d += String.fromCharCode(32 + _ + 128), Q = !1;
              else if (_ < 96)
                Q === ie ? d += String.fromCharCode(_ - 64) : d += String.fromCharCode(_ + 64), Q = !1;
              else
                switch (_ !== V.CODE_STOP && (L = !1), _) {
                  case V.CODE_FNC_1:
                    r && (d.length === 0 ? d += "]C1" : d += "");
                    break;
                  case V.CODE_FNC_2:
                  case V.CODE_FNC_3:
                    break;
                  case V.CODE_FNC_4_A:
                    !ie && Q ? (ie = !0, Q = !1) : ie && Q ? (ie = !1, Q = !1) : Q = !0;
                    break;
                  case V.CODE_SHIFT:
                    f = !0, l = V.CODE_CODE_B;
                    break;
                  case V.CODE_CODE_B:
                    l = V.CODE_CODE_B;
                    break;
                  case V.CODE_CODE_C:
                    l = V.CODE_CODE_C;
                    break;
                  case V.CODE_STOP:
                    c = !0;
                    break;
                }
              break;
            case V.CODE_CODE_B:
              if (_ < 96)
                Q === ie ? d += String.fromCharCode(32 + _) : d += String.fromCharCode(32 + _ + 128), Q = !1;
              else
                switch (_ !== V.CODE_STOP && (L = !1), _) {
                  case V.CODE_FNC_1:
                    r && (d.length === 0 ? d += "]C1" : d += "");
                    break;
                  case V.CODE_FNC_2:
                  case V.CODE_FNC_3:
                    break;
                  case V.CODE_FNC_4_B:
                    !ie && Q ? (ie = !0, Q = !1) : ie && Q ? (ie = !1, Q = !1) : Q = !0;
                    break;
                  case V.CODE_SHIFT:
                    f = !0, l = V.CODE_CODE_A;
                    break;
                  case V.CODE_CODE_A:
                    l = V.CODE_CODE_A;
                    break;
                  case V.CODE_CODE_C:
                    l = V.CODE_CODE_C;
                    break;
                  case V.CODE_STOP:
                    c = !0;
                    break;
                }
              break;
            case V.CODE_CODE_C:
              if (_ < 100)
                _ < 10 && (d += "0"), d += _;
              else
                switch (_ !== V.CODE_STOP && (L = !1), _) {
                  case V.CODE_FNC_1:
                    r && (d.length === 0 ? d += "]C1" : d += "");
                    break;
                  case V.CODE_CODE_A:
                    l = V.CODE_CODE_A;
                    break;
                  case V.CODE_CODE_B:
                    l = V.CODE_CODE_B;
                    break;
                  case V.CODE_STOP:
                    c = !0;
                    break;
                }
              break;
          }
          $t && (l = l === V.CODE_CODE_A ? V.CODE_CODE_B : V.CODE_CODE_A);
        }
        const rt = p - C;
        if (p = t.getNextUnset(p), !t.isRange(p, Math.min(t.getSize(), p + (p - C) / 2), !1))
          throw new O();
        if (P -= F * y, P % 103 !== y)
          throw new J();
        const dt = d.length;
        if (dt === 0)
          throw new O();
        dt > 0 && L && (l === V.CODE_CODE_C ? d = d.substring(0, dt - 2) : d = d.substring(0, dt - 1));
        const it = (i[1] + i[0]) / 2, Ce = C + rt / 2, Ye = a.length, st = new Uint8Array(Ye);
        for (let $t = 0; $t < Ye; $t++)
          st[$t] = a[$t];
        const Jt = [new W(it, e), new W(Ce, e)];
        return new je(d, st, 0, Jt, z.CODE_128, (/* @__PURE__ */ new Date()).getTime());
      }
    }
    V.CODE_PATTERNS = [
      Int32Array.from([2, 1, 2, 2, 2, 2]),
      Int32Array.from([2, 2, 2, 1, 2, 2]),
      Int32Array.from([2, 2, 2, 2, 2, 1]),
      Int32Array.from([1, 2, 1, 2, 2, 3]),
      Int32Array.from([1, 2, 1, 3, 2, 2]),
      Int32Array.from([1, 3, 1, 2, 2, 2]),
      Int32Array.from([1, 2, 2, 2, 1, 3]),
      Int32Array.from([1, 2, 2, 3, 1, 2]),
      Int32Array.from([1, 3, 2, 2, 1, 2]),
      Int32Array.from([2, 2, 1, 2, 1, 3]),
      Int32Array.from([2, 2, 1, 3, 1, 2]),
      Int32Array.from([2, 3, 1, 2, 1, 2]),
      Int32Array.from([1, 1, 2, 2, 3, 2]),
      Int32Array.from([1, 2, 2, 1, 3, 2]),
      Int32Array.from([1, 2, 2, 2, 3, 1]),
      Int32Array.from([1, 1, 3, 2, 2, 2]),
      Int32Array.from([1, 2, 3, 1, 2, 2]),
      Int32Array.from([1, 2, 3, 2, 2, 1]),
      Int32Array.from([2, 2, 3, 2, 1, 1]),
      Int32Array.from([2, 2, 1, 1, 3, 2]),
      Int32Array.from([2, 2, 1, 2, 3, 1]),
      Int32Array.from([2, 1, 3, 2, 1, 2]),
      Int32Array.from([2, 2, 3, 1, 1, 2]),
      Int32Array.from([3, 1, 2, 1, 3, 1]),
      Int32Array.from([3, 1, 1, 2, 2, 2]),
      Int32Array.from([3, 2, 1, 1, 2, 2]),
      Int32Array.from([3, 2, 1, 2, 2, 1]),
      Int32Array.from([3, 1, 2, 2, 1, 2]),
      Int32Array.from([3, 2, 2, 1, 1, 2]),
      Int32Array.from([3, 2, 2, 2, 1, 1]),
      Int32Array.from([2, 1, 2, 1, 2, 3]),
      Int32Array.from([2, 1, 2, 3, 2, 1]),
      Int32Array.from([2, 3, 2, 1, 2, 1]),
      Int32Array.from([1, 1, 1, 3, 2, 3]),
      Int32Array.from([1, 3, 1, 1, 2, 3]),
      Int32Array.from([1, 3, 1, 3, 2, 1]),
      Int32Array.from([1, 1, 2, 3, 1, 3]),
      Int32Array.from([1, 3, 2, 1, 1, 3]),
      Int32Array.from([1, 3, 2, 3, 1, 1]),
      Int32Array.from([2, 1, 1, 3, 1, 3]),
      Int32Array.from([2, 3, 1, 1, 1, 3]),
      Int32Array.from([2, 3, 1, 3, 1, 1]),
      Int32Array.from([1, 1, 2, 1, 3, 3]),
      Int32Array.from([1, 1, 2, 3, 3, 1]),
      Int32Array.from([1, 3, 2, 1, 3, 1]),
      Int32Array.from([1, 1, 3, 1, 2, 3]),
      Int32Array.from([1, 1, 3, 3, 2, 1]),
      Int32Array.from([1, 3, 3, 1, 2, 1]),
      Int32Array.from([3, 1, 3, 1, 2, 1]),
      Int32Array.from([2, 1, 1, 3, 3, 1]),
      Int32Array.from([2, 3, 1, 1, 3, 1]),
      Int32Array.from([2, 1, 3, 1, 1, 3]),
      Int32Array.from([2, 1, 3, 3, 1, 1]),
      Int32Array.from([2, 1, 3, 1, 3, 1]),
      Int32Array.from([3, 1, 1, 1, 2, 3]),
      Int32Array.from([3, 1, 1, 3, 2, 1]),
      Int32Array.from([3, 3, 1, 1, 2, 1]),
      Int32Array.from([3, 1, 2, 1, 1, 3]),
      Int32Array.from([3, 1, 2, 3, 1, 1]),
      Int32Array.from([3, 3, 2, 1, 1, 1]),
      Int32Array.from([3, 1, 4, 1, 1, 1]),
      Int32Array.from([2, 2, 1, 4, 1, 1]),
      Int32Array.from([4, 3, 1, 1, 1, 1]),
      Int32Array.from([1, 1, 1, 2, 2, 4]),
      Int32Array.from([1, 1, 1, 4, 2, 2]),
      Int32Array.from([1, 2, 1, 1, 2, 4]),
      Int32Array.from([1, 2, 1, 4, 2, 1]),
      Int32Array.from([1, 4, 1, 1, 2, 2]),
      Int32Array.from([1, 4, 1, 2, 2, 1]),
      Int32Array.from([1, 1, 2, 2, 1, 4]),
      Int32Array.from([1, 1, 2, 4, 1, 2]),
      Int32Array.from([1, 2, 2, 1, 1, 4]),
      Int32Array.from([1, 2, 2, 4, 1, 1]),
      Int32Array.from([1, 4, 2, 1, 1, 2]),
      Int32Array.from([1, 4, 2, 2, 1, 1]),
      Int32Array.from([2, 4, 1, 2, 1, 1]),
      Int32Array.from([2, 2, 1, 1, 1, 4]),
      Int32Array.from([4, 1, 3, 1, 1, 1]),
      Int32Array.from([2, 4, 1, 1, 1, 2]),
      Int32Array.from([1, 3, 4, 1, 1, 1]),
      Int32Array.from([1, 1, 1, 2, 4, 2]),
      Int32Array.from([1, 2, 1, 1, 4, 2]),
      Int32Array.from([1, 2, 1, 2, 4, 1]),
      Int32Array.from([1, 1, 4, 2, 1, 2]),
      Int32Array.from([1, 2, 4, 1, 1, 2]),
      Int32Array.from([1, 2, 4, 2, 1, 1]),
      Int32Array.from([4, 1, 1, 2, 1, 2]),
      Int32Array.from([4, 2, 1, 1, 1, 2]),
      Int32Array.from([4, 2, 1, 2, 1, 1]),
      Int32Array.from([2, 1, 2, 1, 4, 1]),
      Int32Array.from([2, 1, 4, 1, 2, 1]),
      Int32Array.from([4, 1, 2, 1, 2, 1]),
      Int32Array.from([1, 1, 1, 1, 4, 3]),
      Int32Array.from([1, 1, 1, 3, 4, 1]),
      Int32Array.from([1, 3, 1, 1, 4, 1]),
      Int32Array.from([1, 1, 4, 1, 1, 3]),
      Int32Array.from([1, 1, 4, 3, 1, 1]),
      Int32Array.from([4, 1, 1, 1, 1, 3]),
      Int32Array.from([4, 1, 1, 3, 1, 1]),
      Int32Array.from([1, 1, 3, 1, 4, 1]),
      Int32Array.from([1, 1, 4, 1, 3, 1]),
      Int32Array.from([3, 1, 1, 1, 4, 1]),
      Int32Array.from([4, 1, 1, 1, 3, 1]),
      Int32Array.from([2, 1, 1, 4, 1, 2]),
      Int32Array.from([2, 1, 1, 2, 1, 4]),
      Int32Array.from([2, 1, 1, 2, 3, 2]),
      Int32Array.from([2, 3, 3, 1, 1, 1, 2])
    ], V.MAX_AVG_VARIANCE = 0.25, V.MAX_INDIVIDUAL_VARIANCE = 0.7, V.CODE_SHIFT = 98, V.CODE_CODE_C = 99, V.CODE_CODE_B = 100, V.CODE_CODE_A = 101, V.CODE_FNC_1 = 102, V.CODE_FNC_2 = 97, V.CODE_FNC_3 = 96, V.CODE_FNC_4_A = 101, V.CODE_FNC_4_B = 100, V.CODE_START_A = 103, V.CODE_START_B = 104, V.CODE_START_C = 105, V.CODE_STOP = 106;
    class ve extends Me {
      /**
       * Creates a reader that assumes all encoded data is data, and does not treat the final
       * character as a check digit. It will not decoded "extended Code 39" sequences.
       */
      // public Code39Reader() {
      //   this(false);
      // }
      /**
       * Creates a reader that can be configured to check the last character as a check digit.
       * It will not decoded "extended Code 39" sequences.
       *
       * @param usingCheckDigit if true, treat the last data character as a check digit, not
       * data, and verify that the checksum passes.
       */
      // public Code39Reader(boolean usingCheckDigit) {
      //   this(usingCheckDigit, false);
      // }
      /**
       * Creates a reader that can be configured to check the last character as a check digit,
       * or optionally attempt to decode "extended Code 39" sequences that are used to encode
       * the full ASCII character set.
       *
       * @param usingCheckDigit if true, treat the last data character as a check digit, not
       * data, and verify that the checksum passes.
       * @param extendedMode if true, will attempt to decode extended Code 39 sequences in the
       * text.
       */
      constructor(e = !1, t = !1) {
        super(), this.usingCheckDigit = e, this.extendedMode = t, this.decodeRowResult = "", this.counters = new Int32Array(9);
      }
      decodeRow(e, t, n) {
        let r = this.counters;
        r.fill(0), this.decodeRowResult = "";
        let i = ve.findAsteriskPattern(t, r), s = t.getNextSet(i[1]), o = t.getSize(), a, l;
        do {
          ve.recordPattern(t, s, r);
          let b = ve.toNarrowWidePattern(r);
          if (b < 0)
            throw new O();
          a = ve.patternToChar(b), this.decodeRowResult += a, l = s;
          for (let y of r)
            s += y;
          s = t.getNextSet(s);
        } while (a !== "*");
        this.decodeRowResult = this.decodeRowResult.substring(0, this.decodeRowResult.length - 1);
        let c = 0;
        for (let b of r)
          c += b;
        let f = s - l - c;
        if (s !== o && f * 2 < c)
          throw new O();
        if (this.usingCheckDigit) {
          let b = this.decodeRowResult.length - 1, y = 0;
          for (let _ = 0; _ < b; _++)
            y += ve.ALPHABET_STRING.indexOf(this.decodeRowResult.charAt(_));
          if (this.decodeRowResult.charAt(b) !== ve.ALPHABET_STRING.charAt(y % 43))
            throw new J();
          this.decodeRowResult = this.decodeRowResult.substring(0, b);
        }
        if (this.decodeRowResult.length === 0)
          throw new O();
        let d;
        this.extendedMode ? d = ve.decodeExtended(this.decodeRowResult) : d = this.decodeRowResult;
        let C = (i[1] + i[0]) / 2, p = l + c / 2;
        return new je(d, null, 0, [new W(C, e), new W(p, e)], z.CODE_39, (/* @__PURE__ */ new Date()).getTime());
      }
      static findAsteriskPattern(e, t) {
        let n = e.getSize(), r = e.getNextSet(0), i = 0, s = r, o = !1, a = t.length;
        for (let l = r; l < n; l++)
          if (e.get(l) !== o)
            t[i]++;
          else {
            if (i === a - 1) {
              if (this.toNarrowWidePattern(t) === ve.ASTERISK_ENCODING && e.isRange(Math.max(0, s - Math.floor((l - s) / 2)), s, !1))
                return [s, l];
              s += t[0] + t[1], t.copyWithin(0, 2, 2 + i - 1), t[i - 1] = 0, t[i] = 0, i--;
            } else
              i++;
            t[i] = 1, o = !o;
          }
        throw new O();
      }
      // For efficiency, returns -1 on failure. Not throwing here saved as many as 700 exceptions
      // per image when using some of our blackbox images.
      static toNarrowWidePattern(e) {
        let t = e.length, n = 0, r;
        do {
          let i = 2147483647;
          for (let a of e)
            a < i && a > n && (i = a);
          n = i, r = 0;
          let s = 0, o = 0;
          for (let a = 0; a < t; a++) {
            let l = e[a];
            l > n && (o |= 1 << t - 1 - a, r++, s += l);
          }
          if (r === 3) {
            for (let a = 0; a < t && r > 0; a++) {
              let l = e[a];
              if (l > n && (r--, l * 2 >= s))
                return -1;
            }
            return o;
          }
        } while (r > 3);
        return -1;
      }
      static patternToChar(e) {
        for (let t = 0; t < ve.CHARACTER_ENCODINGS.length; t++)
          if (ve.CHARACTER_ENCODINGS[t] === e)
            return ve.ALPHABET_STRING.charAt(t);
        if (e === ve.ASTERISK_ENCODING)
          return "*";
        throw new O();
      }
      static decodeExtended(e) {
        let t = e.length, n = "";
        for (let r = 0; r < t; r++) {
          let i = e.charAt(r);
          if (i === "+" || i === "$" || i === "%" || i === "/") {
            let s = e.charAt(r + 1), o = "\0";
            switch (i) {
              case "+":
                if (s >= "A" && s <= "Z")
                  o = String.fromCharCode(s.charCodeAt(0) + 32);
                else
                  throw new U();
                break;
              case "$":
                if (s >= "A" && s <= "Z")
                  o = String.fromCharCode(s.charCodeAt(0) - 64);
                else
                  throw new U();
                break;
              case "%":
                if (s >= "A" && s <= "E")
                  o = String.fromCharCode(s.charCodeAt(0) - 38);
                else if (s >= "F" && s <= "J")
                  o = String.fromCharCode(s.charCodeAt(0) - 11);
                else if (s >= "K" && s <= "O")
                  o = String.fromCharCode(s.charCodeAt(0) + 16);
                else if (s >= "P" && s <= "T")
                  o = String.fromCharCode(s.charCodeAt(0) + 43);
                else if (s === "U")
                  o = "\0";
                else if (s === "V")
                  o = "@";
                else if (s === "W")
                  o = "`";
                else if (s === "X" || s === "Y" || s === "Z")
                  o = "";
                else
                  throw new U();
                break;
              case "/":
                if (s >= "A" && s <= "O")
                  o = String.fromCharCode(s.charCodeAt(0) - 32);
                else if (s === "Z")
                  o = ":";
                else
                  throw new U();
                break;
            }
            n += o, r++;
          } else
            n += i;
        }
        return n;
      }
    }
    ve.ALPHABET_STRING = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%", ve.CHARACTER_ENCODINGS = [
      52,
      289,
      97,
      352,
      49,
      304,
      112,
      37,
      292,
      100,
      265,
      73,
      328,
      25,
      280,
      88,
      13,
      268,
      76,
      28,
      259,
      67,
      322,
      19,
      274,
      82,
      7,
      262,
      70,
      22,
      385,
      193,
      448,
      145,
      400,
      208,
      133,
      388,
      196,
      168,
      162,
      138,
      42
      // /-%
    ], ve.ASTERISK_ENCODING = 148;
    class Ae extends Me {
      constructor() {
        super(...arguments), this.narrowLineWidth = -1;
      }
      // See ITFWriter.PATTERNS
      /*
      
        /!**
         * Patterns of Wide / Narrow lines to indicate each digit
         *!/
        */
      decodeRow(e, t, n) {
        let r = this.decodeStart(t), i = this.decodeEnd(t), s = new ge();
        Ae.decodeMiddle(t, r[1], i[0], s);
        let o = s.toString(), a = null;
        n != null && (a = n.get(xe.ALLOWED_LENGTHS)), a == null && (a = Ae.DEFAULT_ALLOWED_LENGTHS);
        let l = o.length, c = !1, f = 0;
        for (let p of a) {
          if (l === p) {
            c = !0;
            break;
          }
          p > f && (f = p);
        }
        if (!c && l > f && (c = !0), !c)
          throw new U();
        const d = [new W(r[1], e), new W(i[0], e)];
        return new je(
          o,
          null,
          // no natural byte representation for these barcodes
          0,
          d,
          z.ITF,
          (/* @__PURE__ */ new Date()).getTime()
        );
      }
      /*
      /!**
       * @param row          row of black/white values to search
       * @param payloadStart offset of start pattern
       * @param resultString {@link StringBuilder} to append decoded chars to
       * @throws NotFoundException if decoding could not complete successfully
       *!/*/
      static decodeMiddle(e, t, n, r) {
        let i = new Int32Array(10), s = new Int32Array(5), o = new Int32Array(5);
        for (i.fill(0), s.fill(0), o.fill(0); t < n; ) {
          Me.recordPattern(e, t, i);
          for (let l = 0; l < 5; l++) {
            let c = 2 * l;
            s[l] = i[c], o[l] = i[c + 1];
          }
          let a = Ae.decodeDigit(s);
          r.append(a.toString()), a = this.decodeDigit(o), r.append(a.toString()), i.forEach(function(l) {
            t += l;
          });
        }
      }
      /*/!**
       * Identify where the start of the middle / payload section starts.
       *
       * @param row row of black/white values to search
       * @return Array, containing index of start of 'start block' and end of
       *         'start block'
       *!/*/
      decodeStart(e) {
        let t = Ae.skipWhiteSpace(e), n = Ae.findGuardPattern(e, t, Ae.START_PATTERN);
        return this.narrowLineWidth = (n[1] - n[0]) / 4, this.validateQuietZone(e, n[0]), n;
      }
      /*/!**
       * The start & end patterns must be pre/post fixed by a quiet zone. This
       * zone must be at least 10 times the width of a narrow line.  Scan back until
       * we either get to the start of the barcode or match the necessary number of
       * quiet zone pixels.
       *
       * Note: Its assumed the row is reversed when using this method to find
       * quiet zone after the end pattern.
       *
       * ref: http://www.barcode-1.net/i25code.html
       *
       * @param row bit array representing the scanned barcode.
       * @param startPattern index into row of the start or end pattern.
       * @throws NotFoundException if the quiet zone cannot be found
       *!/*/
      validateQuietZone(e, t) {
        let n = this.narrowLineWidth * 10;
        n = n < t ? n : t;
        for (let r = t - 1; n > 0 && r >= 0 && !e.get(r); r--)
          n--;
        if (n !== 0)
          throw new O();
      }
      /*
      /!**
       * Skip all whitespace until we get to the first black line.
       *
       * @param row row of black/white values to search
       * @return index of the first black line.
       * @throws NotFoundException Throws exception if no black lines are found in the row
       *!/*/
      static skipWhiteSpace(e) {
        const t = e.getSize(), n = e.getNextSet(0);
        if (n === t)
          throw new O();
        return n;
      }
      /*/!**
       * Identify where the end of the middle / payload section ends.
       *
       * @param row row of black/white values to search
       * @return Array, containing index of start of 'end block' and end of 'end
       *         block'
       *!/*/
      decodeEnd(e) {
        e.reverse();
        try {
          let t = Ae.skipWhiteSpace(e), n;
          try {
            n = Ae.findGuardPattern(e, t, Ae.END_PATTERN_REVERSED[0]);
          } catch (i) {
            i instanceof O && (n = Ae.findGuardPattern(e, t, Ae.END_PATTERN_REVERSED[1]));
          }
          this.validateQuietZone(e, n[0]);
          let r = n[0];
          return n[0] = e.getSize() - n[1], n[1] = e.getSize() - r, n;
        } finally {
          e.reverse();
        }
      }
      /*
      /!**
       * @param row       row of black/white values to search
       * @param rowOffset position to start search
       * @param pattern   pattern of counts of number of black and white pixels that are
       *                  being searched for as a pattern
       * @return start/end horizontal offset of guard pattern, as an array of two
       *         ints
       * @throws NotFoundException if pattern is not found
       *!/*/
      static findGuardPattern(e, t, n) {
        let r = n.length, i = new Int32Array(r), s = e.getSize(), o = !1, a = 0, l = t;
        i.fill(0);
        for (let c = t; c < s; c++)
          if (e.get(c) !== o)
            i[a]++;
          else {
            if (a === r - 1) {
              if (Me.patternMatchVariance(i, n, Ae.MAX_INDIVIDUAL_VARIANCE) < Ae.MAX_AVG_VARIANCE)
                return [l, c];
              l += i[0] + i[1], re.arraycopy(i, 2, i, 0, a - 1), i[a - 1] = 0, i[a] = 0, a--;
            } else
              a++;
            i[a] = 1, o = !o;
          }
        throw new O();
      }
      /*/!**
       * Attempts to decode a sequence of ITF black/white lines into single
       * digit.
       *
       * @param counters the counts of runs of observed black/white/black/... values
       * @return The decoded digit
       * @throws NotFoundException if digit cannot be decoded
       *!/*/
      static decodeDigit(e) {
        let t = Ae.MAX_AVG_VARIANCE, n = -1, r = Ae.PATTERNS.length;
        for (let i = 0; i < r; i++) {
          let s = Ae.PATTERNS[i], o = Me.patternMatchVariance(e, s, Ae.MAX_INDIVIDUAL_VARIANCE);
          o < t ? (t = o, n = i) : o === t && (n = -1);
        }
        if (n >= 0)
          return n % 10;
        throw new O();
      }
    }
    Ae.PATTERNS = [
      Int32Array.from([1, 1, 2, 2, 1]),
      Int32Array.from([2, 1, 1, 1, 2]),
      Int32Array.from([1, 2, 1, 1, 2]),
      Int32Array.from([2, 2, 1, 1, 1]),
      Int32Array.from([1, 1, 2, 1, 2]),
      Int32Array.from([2, 1, 2, 1, 1]),
      Int32Array.from([1, 2, 2, 1, 1]),
      Int32Array.from([1, 1, 1, 2, 2]),
      Int32Array.from([2, 1, 1, 2, 1]),
      Int32Array.from([1, 2, 1, 2, 1]),
      Int32Array.from([1, 1, 3, 3, 1]),
      Int32Array.from([3, 1, 1, 1, 3]),
      Int32Array.from([1, 3, 1, 1, 3]),
      Int32Array.from([3, 3, 1, 1, 1]),
      Int32Array.from([1, 1, 3, 1, 3]),
      Int32Array.from([3, 1, 3, 1, 1]),
      Int32Array.from([1, 3, 3, 1, 1]),
      Int32Array.from([1, 1, 1, 3, 3]),
      Int32Array.from([3, 1, 1, 3, 1]),
      Int32Array.from([1, 3, 1, 3, 1])
      // 9
    ], Ae.MAX_AVG_VARIANCE = 0.38, Ae.MAX_INDIVIDUAL_VARIANCE = 0.5, Ae.DEFAULT_ALLOWED_LENGTHS = [6, 8, 10, 12, 14], Ae.START_PATTERN = Int32Array.from([1, 1, 1, 1]), Ae.END_PATTERN_REVERSED = [
      Int32Array.from([1, 1, 2]),
      Int32Array.from([1, 1, 3])
      // 3x
    ];
    class Ne extends Me {
      constructor() {
        super(...arguments), this.decodeRowStringBuffer = "";
      }
      static findStartGuardPattern(e) {
        let t = !1, n, r = 0, i = Int32Array.from([0, 0, 0]);
        for (; !t; ) {
          i = Int32Array.from([0, 0, 0]), n = Ne.findGuardPattern(e, r, !1, this.START_END_PATTERN, i);
          let s = n[0];
          r = n[1];
          let o = s - (r - s);
          o >= 0 && (t = e.isRange(o, s, !1));
        }
        return n;
      }
      static checkChecksum(e) {
        return Ne.checkStandardUPCEANChecksum(e);
      }
      static checkStandardUPCEANChecksum(e) {
        let t = e.length;
        if (t === 0)
          return !1;
        let n = parseInt(e.charAt(t - 1), 10);
        return Ne.getStandardUPCEANChecksum(e.substring(0, t - 1)) === n;
      }
      static getStandardUPCEANChecksum(e) {
        let t = e.length, n = 0;
        for (let r = t - 1; r >= 0; r -= 2) {
          let i = e.charAt(r).charCodeAt(0) - 48;
          if (i < 0 || i > 9)
            throw new U();
          n += i;
        }
        n *= 3;
        for (let r = t - 2; r >= 0; r -= 2) {
          let i = e.charAt(r).charCodeAt(0) - 48;
          if (i < 0 || i > 9)
            throw new U();
          n += i;
        }
        return (1e3 - n) % 10;
      }
      static decodeEnd(e, t) {
        return Ne.findGuardPattern(e, t, !1, Ne.START_END_PATTERN, new Int32Array(Ne.START_END_PATTERN.length).fill(0));
      }
      /**
       * @throws NotFoundException
       */
      static findGuardPatternWithoutCounters(e, t, n, r) {
        return this.findGuardPattern(e, t, n, r, new Int32Array(r.length));
      }
      /**
       * @param row row of black/white values to search
       * @param rowOffset position to start search
       * @param whiteFirst if true, indicates that the pattern specifies white/black/white/...
       * pixel counts, otherwise, it is interpreted as black/white/black/...
       * @param pattern pattern of counts of number of black and white pixels that are being
       * searched for as a pattern
       * @param counters array of counters, as long as pattern, to re-use
       * @return start/end horizontal offset of guard pattern, as an array of two ints
       * @throws NotFoundException if pattern is not found
       */
      static findGuardPattern(e, t, n, r, i) {
        let s = e.getSize();
        t = n ? e.getNextUnset(t) : e.getNextSet(t);
        let o = 0, a = t, l = r.length, c = n;
        for (let f = t; f < s; f++)
          if (e.get(f) !== c)
            i[o]++;
          else {
            if (o === l - 1) {
              if (Me.patternMatchVariance(i, r, Ne.MAX_INDIVIDUAL_VARIANCE) < Ne.MAX_AVG_VARIANCE)
                return Int32Array.from([a, f]);
              a += i[0] + i[1];
              let d = i.slice(2, i.length - 1);
              for (let C = 0; C < o - 1; C++)
                i[C] = d[C];
              i[o - 1] = 0, i[o] = 0, o--;
            } else
              o++;
            i[o] = 1, c = !c;
          }
        throw new O();
      }
      static decodeDigit(e, t, n, r) {
        this.recordPattern(e, n, t);
        let i = this.MAX_AVG_VARIANCE, s = -1, o = r.length;
        for (let a = 0; a < o; a++) {
          let l = r[a], c = Me.patternMatchVariance(t, l, Ne.MAX_INDIVIDUAL_VARIANCE);
          c < i && (i = c, s = a);
        }
        if (s >= 0)
          return s;
        throw new O();
      }
    }
    Ne.MAX_AVG_VARIANCE = 0.48, Ne.MAX_INDIVIDUAL_VARIANCE = 0.7, Ne.START_END_PATTERN = Int32Array.from([1, 1, 1]), Ne.MIDDLE_PATTERN = Int32Array.from([1, 1, 1, 1, 1]), Ne.END_PATTERN = Int32Array.from([1, 1, 1, 1, 1, 1]), Ne.L_PATTERNS = [
      Int32Array.from([3, 2, 1, 1]),
      Int32Array.from([2, 2, 2, 1]),
      Int32Array.from([2, 1, 2, 2]),
      Int32Array.from([1, 4, 1, 1]),
      Int32Array.from([1, 1, 3, 2]),
      Int32Array.from([1, 2, 3, 1]),
      Int32Array.from([1, 1, 1, 4]),
      Int32Array.from([1, 3, 1, 2]),
      Int32Array.from([1, 2, 1, 3]),
      Int32Array.from([3, 1, 1, 2])
    ];
    class on {
      constructor() {
        this.CHECK_DIGIT_ENCODINGS = [24, 20, 18, 17, 12, 6, 3, 10, 9, 5], this.decodeMiddleCounters = Int32Array.from([0, 0, 0, 0]), this.decodeRowStringBuffer = "";
      }
      decodeRow(e, t, n) {
        let r = this.decodeRowStringBuffer, i = this.decodeMiddle(t, n, r), s = r.toString(), o = on.parseExtensionString(s), a = [
          new W((n[0] + n[1]) / 2, e),
          new W(i, e)
        ], l = new je(s, null, 0, a, z.UPC_EAN_EXTENSION, (/* @__PURE__ */ new Date()).getTime());
        return o != null && l.putAllMetadata(o), l;
      }
      decodeMiddle(e, t, n) {
        let r = this.decodeMiddleCounters;
        r[0] = 0, r[1] = 0, r[2] = 0, r[3] = 0;
        let i = e.getSize(), s = t[1], o = 0;
        for (let l = 0; l < 5 && s < i; l++) {
          let c = Ne.decodeDigit(
            e,
            r,
            s,
            Ne.L_AND_G_PATTERNS
          );
          n += String.fromCharCode(48 + c % 10);
          for (let f of r)
            s += f;
          c >= 10 && (o |= 1 << 4 - l), l !== 4 && (s = e.getNextSet(s), s = e.getNextUnset(s));
        }
        if (n.length !== 5)
          throw new O();
        let a = this.determineCheckDigit(o);
        if (on.extensionChecksum(n.toString()) !== a)
          throw new O();
        return s;
      }
      static extensionChecksum(e) {
        let t = e.length, n = 0;
        for (let r = t - 2; r >= 0; r -= 2)
          n += e.charAt(r).charCodeAt(0) - 48;
        n *= 3;
        for (let r = t - 1; r >= 0; r -= 2)
          n += e.charAt(r).charCodeAt(0) - 48;
        return n *= 3, n % 10;
      }
      determineCheckDigit(e) {
        for (let t = 0; t < 10; t++)
          if (e === this.CHECK_DIGIT_ENCODINGS[t])
            return t;
        throw new O();
      }
      static parseExtensionString(e) {
        if (e.length !== 5)
          return null;
        let t = on.parseExtension5String(e);
        return t == null ? null : /* @__PURE__ */ new Map([[Ue.SUGGESTED_PRICE, t]]);
      }
      static parseExtension5String(e) {
        let t;
        switch (e.charAt(0)) {
          case "0":
            t = "£";
            break;
          case "5":
            t = "$";
            break;
          case "9":
            switch (e) {
              case "90000":
                return null;
              case "99991":
                return "0.00";
              case "99990":
                return "Used";
            }
            t = "";
            break;
          default:
            t = "";
            break;
        }
        let n = parseInt(e.substring(1)), r = (n / 100).toString(), i = n % 100, s = i < 10 ? "0" + i : i.toString();
        return t + r + "." + s;
      }
    }
    class Fn {
      constructor() {
        this.decodeMiddleCounters = Int32Array.from([0, 0, 0, 0]), this.decodeRowStringBuffer = "";
      }
      decodeRow(e, t, n) {
        let r = this.decodeRowStringBuffer, i = this.decodeMiddle(t, n, r), s = r.toString(), o = Fn.parseExtensionString(s), a = [
          new W((n[0] + n[1]) / 2, e),
          new W(i, e)
        ], l = new je(s, null, 0, a, z.UPC_EAN_EXTENSION, (/* @__PURE__ */ new Date()).getTime());
        return o != null && l.putAllMetadata(o), l;
      }
      decodeMiddle(e, t, n) {
        let r = this.decodeMiddleCounters;
        r[0] = 0, r[1] = 0, r[2] = 0, r[3] = 0;
        let i = e.getSize(), s = t[1], o = 0;
        for (let a = 0; a < 2 && s < i; a++) {
          let l = Ne.decodeDigit(e, r, s, Ne.L_AND_G_PATTERNS);
          n += String.fromCharCode(48 + l % 10);
          for (let c of r)
            s += c;
          l >= 10 && (o |= 1 << 1 - a), a !== 1 && (s = e.getNextSet(s), s = e.getNextUnset(s));
        }
        if (n.length !== 2)
          throw new O();
        if (parseInt(n.toString()) % 4 !== o)
          throw new O();
        return s;
      }
      static parseExtensionString(e) {
        return e.length !== 2 ? null : /* @__PURE__ */ new Map([[Ue.ISSUE_NUMBER, parseInt(e)]]);
      }
    }
    class cr {
      static decodeRow(e, t, n) {
        let r = Ne.findGuardPattern(
          t,
          n,
          !1,
          this.EXTENSION_START_PATTERN,
          new Int32Array(this.EXTENSION_START_PATTERN.length).fill(0)
        );
        try {
          return new on().decodeRow(e, t, r);
        } catch {
          return new Fn().decodeRow(e, t, r);
        }
      }
    }
    cr.EXTENSION_START_PATTERN = Int32Array.from([1, 1, 2]);
    class he extends Ne {
      constructor() {
        super(), this.decodeRowStringBuffer = "", he.L_AND_G_PATTERNS = he.L_PATTERNS.map((e) => Int32Array.from(e));
        for (let e = 10; e < 20; e++) {
          let t = he.L_PATTERNS[e - 10], n = new Int32Array(t.length);
          for (let r = 0; r < t.length; r++)
            n[r] = t[t.length - r - 1];
          he.L_AND_G_PATTERNS[e] = n;
        }
      }
      decodeRow(e, t, n) {
        let r = he.findStartGuardPattern(t), i = n == null ? null : n.get(xe.NEED_RESULT_POINT_CALLBACK);
        if (i != null) {
          const L = new W((r[0] + r[1]) / 2, e);
          i.foundPossibleResultPoint(L);
        }
        let s = this.decodeMiddle(t, r, this.decodeRowStringBuffer), o = s.rowOffset, a = s.resultString;
        if (i != null) {
          const L = new W(o, e);
          i.foundPossibleResultPoint(L);
        }
        let l = this.decodeEnd(t, o);
        if (i != null) {
          const L = new W((l[0] + l[1]) / 2, e);
          i.foundPossibleResultPoint(L);
        }
        let c = l[1], f = c + (c - l[0]);
        if (f >= t.getSize() || !t.isRange(c, f, !1))
          throw new O();
        let d = a.toString();
        if (d.length < 8)
          throw new U();
        if (!he.checkChecksum(d))
          throw new J();
        let C = (r[1] + r[0]) / 2, p = (l[1] + l[0]) / 2, b = this.getBarcodeFormat(), y = [new W(C, e), new W(p, e)], _ = new je(d, null, 0, y, b, (/* @__PURE__ */ new Date()).getTime()), P = 0;
        try {
          let L = cr.decodeRow(e, t, l[1]);
          _.putMetadata(Ue.UPC_EAN_EXTENSION, L.getText()), _.putAllMetadata(L.getResultMetadata()), _.addResultPoints(L.getResultPoints()), P = L.getText().length;
        } catch {
        }
        let F = n == null ? null : n.get(xe.ALLOWED_EAN_EXTENSIONS);
        if (F != null) {
          let L = !1;
          for (let ie in F)
            if (P.toString() === ie) {
              L = !0;
              break;
            }
          if (!L)
            throw new O();
        }
        return _;
      }
      decodeEnd(e, t) {
        return he.findGuardPattern(
          e,
          t,
          !1,
          he.START_END_PATTERN,
          new Int32Array(he.START_END_PATTERN.length).fill(0)
        );
      }
      static checkChecksum(e) {
        return he.checkStandardUPCEANChecksum(e);
      }
      static checkStandardUPCEANChecksum(e) {
        let t = e.length;
        if (t === 0)
          return !1;
        let n = parseInt(e.charAt(t - 1), 10);
        return he.getStandardUPCEANChecksum(e.substring(0, t - 1)) === n;
      }
      static getStandardUPCEANChecksum(e) {
        let t = e.length, n = 0;
        for (let r = t - 1; r >= 0; r -= 2) {
          let i = e.charAt(r).charCodeAt(0) - 48;
          if (i < 0 || i > 9)
            throw new U();
          n += i;
        }
        n *= 3;
        for (let r = t - 2; r >= 0; r -= 2) {
          let i = e.charAt(r).charCodeAt(0) - 48;
          if (i < 0 || i > 9)
            throw new U();
          n += i;
        }
        return (1e3 - n) % 10;
      }
    }
    class Pt extends he {
      constructor() {
        super(), this.decodeMiddleCounters = Int32Array.from([0, 0, 0, 0]);
      }
      decodeMiddle(e, t, n) {
        let r = this.decodeMiddleCounters;
        r[0] = 0, r[1] = 0, r[2] = 0, r[3] = 0;
        let i = e.getSize(), s = t[1], o = 0;
        for (let l = 0; l < 6 && s < i; l++) {
          let c = he.decodeDigit(e, r, s, he.L_AND_G_PATTERNS);
          n += String.fromCharCode(48 + c % 10);
          for (let f of r)
            s += f;
          c >= 10 && (o |= 1 << 5 - l);
        }
        n = Pt.determineFirstDigit(n, o), s = he.findGuardPattern(
          e,
          s,
          !0,
          he.MIDDLE_PATTERN,
          new Int32Array(he.MIDDLE_PATTERN.length).fill(0)
        )[1];
        for (let l = 0; l < 6 && s < i; l++) {
          let c = he.decodeDigit(e, r, s, he.L_PATTERNS);
          n += String.fromCharCode(48 + c);
          for (let f of r)
            s += f;
        }
        return { rowOffset: s, resultString: n };
      }
      getBarcodeFormat() {
        return z.EAN_13;
      }
      static determineFirstDigit(e, t) {
        for (let n = 0; n < 10; n++)
          if (t === this.FIRST_DIGIT_ENCODINGS[n])
            return e = String.fromCharCode(48 + n) + e, e;
        throw new O();
      }
    }
    Pt.FIRST_DIGIT_ENCODINGS = [0, 11, 13, 14, 19, 25, 28, 21, 22, 26];
    class hr extends he {
      constructor() {
        super(), this.decodeMiddleCounters = Int32Array.from([0, 0, 0, 0]);
      }
      decodeMiddle(e, t, n) {
        const r = this.decodeMiddleCounters;
        r[0] = 0, r[1] = 0, r[2] = 0, r[3] = 0;
        let i = e.getSize(), s = t[1];
        for (let a = 0; a < 4 && s < i; a++) {
          let l = he.decodeDigit(e, r, s, he.L_PATTERNS);
          n += String.fromCharCode(48 + l);
          for (let c of r)
            s += c;
        }
        s = he.findGuardPattern(e, s, !0, he.MIDDLE_PATTERN, new Int32Array(he.MIDDLE_PATTERN.length).fill(0))[1];
        for (let a = 0; a < 4 && s < i; a++) {
          let l = he.decodeDigit(e, r, s, he.L_PATTERNS);
          n += String.fromCharCode(48 + l);
          for (let c of r)
            s += c;
        }
        return { rowOffset: s, resultString: n };
      }
      getBarcodeFormat() {
        return z.EAN_8;
      }
    }
    class ur extends he {
      constructor() {
        super(...arguments), this.ean13Reader = new Pt();
      }
      // @Override
      getBarcodeFormat() {
        return z.UPC_A;
      }
      // Note that we don't try rotation without the try harder flag, even if rotation was supported.
      // @Override
      decode(e, t) {
        return this.maybeReturnResult(this.ean13Reader.decode(e));
      }
      // @Override
      decodeRow(e, t, n) {
        return this.maybeReturnResult(this.ean13Reader.decodeRow(e, t, n));
      }
      // @Override
      decodeMiddle(e, t, n) {
        return this.ean13Reader.decodeMiddle(e, t, n);
      }
      maybeReturnResult(e) {
        let t = e.getText();
        if (t.charAt(0) === "0") {
          let n = new je(t.substring(1), null, null, e.getResultPoints(), z.UPC_A);
          return e.getResultMetadata() != null && n.putAllMetadata(e.getResultMetadata()), n;
        } else
          throw new O();
      }
      reset() {
        this.ean13Reader.reset();
      }
    }
    class ft extends he {
      constructor() {
        super(), this.decodeMiddleCounters = new Int32Array(4);
      }
      /**
       * @throws NotFoundException
       */
      // @Override
      decodeMiddle(e, t, n) {
        const r = this.decodeMiddleCounters.map((l) => l);
        r[0] = 0, r[1] = 0, r[2] = 0, r[3] = 0;
        const i = e.getSize();
        let s = t[1], o = 0;
        for (let l = 0; l < 6 && s < i; l++) {
          const c = ft.decodeDigit(
            e,
            r,
            s,
            ft.L_AND_G_PATTERNS
          );
          n += String.fromCharCode(48 + c % 10);
          for (let f of r)
            s += f;
          c >= 10 && (o |= 1 << 5 - l);
        }
        let a = ft.determineNumSysAndCheckDigit(
          n,
          o
        );
        return { rowOffset: s, resultString: a };
      }
      /**
       * @throws NotFoundException
       */
      // @Override
      decodeEnd(e, t) {
        return ft.findGuardPatternWithoutCounters(
          e,
          t,
          !0,
          ft.MIDDLE_END_PATTERN
        );
      }
      /**
       * @throws FormatException
       */
      // @Override
      checkChecksum(e) {
        return he.checkChecksum(ft.convertUPCEtoUPCA(e));
      }
      /**
       * @throws NotFoundException
       */
      static determineNumSysAndCheckDigit(e, t) {
        for (let n = 0; n <= 1; n++)
          for (let r = 0; r < 10; r++)
            if (t === this.NUMSYS_AND_CHECK_DIGIT_PATTERNS[n][r]) {
              let i = String.fromCharCode(48 + n), s = String.fromCharCode(48 + r);
              return i + e + s;
            }
        throw O.getNotFoundInstance();
      }
      // @Override
      getBarcodeFormat() {
        return z.UPC_E;
      }
      /**
       * Expands a UPC-E value back into its full, equivalent UPC-A code value.
       *
       * @param upce UPC-E code as string of digits
       * @return equivalent UPC-A code as string of digits
       */
      static convertUPCEtoUPCA(e) {
        const t = e.slice(1, 7).split("").map((i) => i.charCodeAt(0)), n = new ge(
          /*12*/
        );
        n.append(e.charAt(0));
        let r = t[5];
        switch (r) {
          case 0:
          case 1:
          case 2:
            n.appendChars(t, 0, 2), n.append(r), n.append("0000"), n.appendChars(t, 2, 3);
            break;
          case 3:
            n.appendChars(t, 0, 3), n.append("00000"), n.appendChars(t, 3, 2);
            break;
          case 4:
            n.appendChars(t, 0, 4), n.append("00000"), n.append(t[4]);
            break;
          default:
            n.appendChars(t, 0, 5), n.append("0000"), n.append(r);
            break;
        }
        return e.length >= 8 && n.append(e.charAt(7)), n.toString();
      }
    }
    ft.MIDDLE_END_PATTERN = Int32Array.from([1, 1, 1, 1, 1, 1]), ft.NUMSYS_AND_CHECK_DIGIT_PATTERNS = [
      Int32Array.from([56, 52, 50, 49, 44, 38, 35, 42, 41, 37]),
      Int32Array.from([7, 11, 13, 14, 19, 25, 28, 21, 22, 26])
    ];
    class kn extends Me {
      constructor(e) {
        super();
        let t = e == null ? null : e.get(xe.POSSIBLE_FORMATS), n = [];
        m(t) ? (n.push(new Pt()), n.push(new ur()), n.push(new hr()), n.push(new ft())) : (t.indexOf(z.EAN_13) > -1 && n.push(new Pt()), t.indexOf(z.UPC_A) > -1 && n.push(new ur()), t.indexOf(z.EAN_8) > -1 && n.push(new hr()), t.indexOf(z.UPC_E) > -1 && n.push(new ft())), this.readers = n;
      }
      decodeRow(e, t, n) {
        for (let r of this.readers)
          try {
            const i = r.decodeRow(e, t, n), s = i.getBarcodeFormat() === z.EAN_13 && i.getText().charAt(0) === "0", o = n == null ? null : n.get(xe.POSSIBLE_FORMATS), a = o == null || o.includes(z.UPC_A);
            if (s && a) {
              const l = i.getRawBytes(), c = new je(
                i.getText().substring(1),
                l,
                l ? l.length : null,
                i.getResultPoints(),
                z.UPC_A
              );
              return c.putAllMetadata(i.getResultMetadata()), c;
            }
            return i;
          } catch {
          }
        throw new O();
      }
      reset() {
        for (let e of this.readers)
          e.reset();
      }
    }
    class ze extends Me {
      constructor() {
        super(), this.decodeFinderCounters = new Int32Array(4), this.dataCharacterCounters = new Int32Array(8), this.oddRoundingErrors = new Array(4), this.evenRoundingErrors = new Array(4), this.oddCounts = new Array(this.dataCharacterCounters.length / 2), this.evenCounts = new Array(this.dataCharacterCounters.length / 2);
      }
      getDecodeFinderCounters() {
        return this.decodeFinderCounters;
      }
      getDataCharacterCounters() {
        return this.dataCharacterCounters;
      }
      getOddRoundingErrors() {
        return this.oddRoundingErrors;
      }
      getEvenRoundingErrors() {
        return this.evenRoundingErrors;
      }
      getOddCounts() {
        return this.oddCounts;
      }
      getEvenCounts() {
        return this.evenCounts;
      }
      parseFinderValue(e, t) {
        for (let n = 0; n < t.length; n++)
          if (Me.patternMatchVariance(e, t[n], ze.MAX_INDIVIDUAL_VARIANCE) < ze.MAX_AVG_VARIANCE)
            return n;
        throw new O();
      }
      /**
       * @param array values to sum
       * @return sum of values
       * @deprecated call {@link MathUtils#sum(int[])}
       */
      static count(e) {
        return oe.sum(new Int32Array(e));
      }
      static increment(e, t) {
        let n = 0, r = t[0];
        for (let i = 1; i < e.length; i++)
          t[i] > r && (r = t[i], n = i);
        e[n]++;
      }
      static decrement(e, t) {
        let n = 0, r = t[0];
        for (let i = 1; i < e.length; i++)
          t[i] < r && (r = t[i], n = i);
        e[n]--;
      }
      static isFinderPattern(e) {
        let t = e[0] + e[1], n = t + e[2] + e[3], r = t / n;
        if (r >= ze.MIN_FINDER_PATTERN_RATIO && r <= ze.MAX_FINDER_PATTERN_RATIO) {
          let i = Number.MAX_SAFE_INTEGER, s = Number.MIN_SAFE_INTEGER;
          for (let o of e)
            o > s && (s = o), o < i && (i = o);
          return s < 10 * i;
        }
        return !1;
      }
    }
    ze.MAX_AVG_VARIANCE = 0.2, ze.MAX_INDIVIDUAL_VARIANCE = 0.45, ze.MIN_FINDER_PATTERN_RATIO = 9.5 / 12, ze.MAX_FINDER_PATTERN_RATIO = 12.5 / 14;
    class Yt {
      constructor(e, t) {
        this.value = e, this.checksumPortion = t;
      }
      getValue() {
        return this.value;
      }
      getChecksumPortion() {
        return this.checksumPortion;
      }
      toString() {
        return this.value + "(" + this.checksumPortion + ")";
      }
      equals(e) {
        if (!(e instanceof Yt))
          return !1;
        const t = e;
        return this.value === t.value && this.checksumPortion === t.checksumPortion;
      }
      hashCode() {
        return this.value ^ this.checksumPortion;
      }
    }
    class In {
      constructor(e, t, n, r, i) {
        this.value = e, this.startEnd = t, this.value = e, this.startEnd = t, this.resultPoints = new Array(), this.resultPoints.push(new W(n, i)), this.resultPoints.push(new W(r, i));
      }
      getValue() {
        return this.value;
      }
      getStartEnd() {
        return this.startEnd;
      }
      getResultPoints() {
        return this.resultPoints;
      }
      equals(e) {
        if (!(e instanceof In))
          return !1;
        const t = e;
        return this.value === t.value;
      }
      hashCode() {
        return this.value;
      }
    }
    class wt {
      constructor() {
      }
      static getRSSvalue(e, t, n) {
        let r = 0;
        for (let a of e)
          r += a;
        let i = 0, s = 0, o = e.length;
        for (let a = 0; a < o - 1; a++) {
          let l;
          for (l = 1, s |= 1 << a; l < e[a]; l++, s &= ~(1 << a)) {
            let c = wt.combins(r - l - 1, o - a - 2);
            if (n && s === 0 && r - l - (o - a - 1) >= o - a - 1 && (c -= wt.combins(r - l - (o - a), o - a - 2)), o - a - 1 > 1) {
              let f = 0;
              for (let d = r - l - (o - a - 2); d > t; d--)
                f += wt.combins(r - l - d - 1, o - a - 3);
              c -= f * (o - 1 - a);
            } else r - l > t && c--;
            i += c;
          }
          r -= l;
        }
        return i;
      }
      static combins(e, t) {
        let n, r;
        e - t > t ? (r = t, n = e - t) : (r = e - t, n = t);
        let i = 1, s = 1;
        for (let o = e; o > n; o--)
          i *= o, s <= r && (i /= s, s++);
        for (; s <= r; )
          i /= s, s++;
        return i;
      }
    }
    class Wr {
      static buildBitArray(e) {
        let t = e.length * 2 - 1;
        e[e.length - 1].getRightChar() == null && (t -= 1);
        let n = 12 * t, r = new le(n), i = 0, o = e[0].getRightChar().getValue();
        for (let a = 11; a >= 0; --a)
          o & 1 << a && r.set(i), i++;
        for (let a = 1; a < e.length; ++a) {
          let l = e[a], c = l.getLeftChar().getValue();
          for (let f = 11; f >= 0; --f)
            c & 1 << f && r.set(i), i++;
          if (l.getRightChar() != null) {
            let f = l.getRightChar().getValue();
            for (let d = 11; d >= 0; --d)
              f & 1 << d && r.set(i), i++;
          }
        }
        return r;
      }
    }
    class Ft {
      constructor(e, t) {
        t ? this.decodedInformation = null : (this.finished = e, this.decodedInformation = t);
      }
      getDecodedInformation() {
        return this.decodedInformation;
      }
      isFinished() {
        return this.finished;
      }
    }
    class Un {
      constructor(e) {
        this.newPosition = e;
      }
      getNewPosition() {
        return this.newPosition;
      }
    }
    class et extends Un {
      constructor(e, t) {
        super(e), this.value = t;
      }
      getValue() {
        return this.value;
      }
      isFNC1() {
        return this.value === et.FNC1;
      }
    }
    et.FNC1 = "$";
    class kt extends Un {
      constructor(e, t, n) {
        super(e), n ? (this.remaining = !0, this.remainingValue = this.remainingValue) : (this.remaining = !1, this.remainingValue = 0), this.newString = t;
      }
      getNewString() {
        return this.newString;
      }
      isRemaining() {
        return this.remaining;
      }
      getRemainingValue() {
        return this.remainingValue;
      }
    }
    class at extends Un {
      constructor(e, t, n) {
        if (super(e), t < 0 || t > 10 || n < 0 || n > 10)
          throw new U();
        this.firstDigit = t, this.secondDigit = n;
      }
      getFirstDigit() {
        return this.firstDigit;
      }
      getSecondDigit() {
        return this.secondDigit;
      }
      getValue() {
        return this.firstDigit * 10 + this.secondDigit;
      }
      isFirstDigitFNC1() {
        return this.firstDigit === at.FNC1;
      }
      isSecondDigitFNC1() {
        return this.secondDigit === at.FNC1;
      }
      isAnyFNC1() {
        return this.firstDigit === at.FNC1 || this.secondDigit === at.FNC1;
      }
    }
    at.FNC1 = 10;
    class G {
      constructor() {
      }
      static parseFieldsInGeneralPurpose(e) {
        if (!e)
          return null;
        if (e.length < 2)
          throw new O();
        let t = e.substring(0, 2);
        for (let i of G.TWO_DIGIT_DATA_LENGTH)
          if (i[0] === t)
            return i[1] === G.VARIABLE_LENGTH ? G.processVariableAI(2, i[2], e) : G.processFixedAI(2, i[1], e);
        if (e.length < 3)
          throw new O();
        let n = e.substring(0, 3);
        for (let i of G.THREE_DIGIT_DATA_LENGTH)
          if (i[0] === n)
            return i[1] === G.VARIABLE_LENGTH ? G.processVariableAI(3, i[2], e) : G.processFixedAI(3, i[1], e);
        for (let i of G.THREE_DIGIT_PLUS_DIGIT_DATA_LENGTH)
          if (i[0] === n)
            return i[1] === G.VARIABLE_LENGTH ? G.processVariableAI(4, i[2], e) : G.processFixedAI(4, i[1], e);
        if (e.length < 4)
          throw new O();
        let r = e.substring(0, 4);
        for (let i of G.FOUR_DIGIT_DATA_LENGTH)
          if (i[0] === r)
            return i[1] === G.VARIABLE_LENGTH ? G.processVariableAI(4, i[2], e) : G.processFixedAI(4, i[1], e);
        throw new O();
      }
      static processFixedAI(e, t, n) {
        if (n.length < e)
          throw new O();
        let r = n.substring(0, e);
        if (n.length < e + t)
          throw new O();
        let i = n.substring(e, e + t), s = n.substring(e + t), o = "(" + r + ")" + i, a = G.parseFieldsInGeneralPurpose(s);
        return a == null ? o : o + a;
      }
      static processVariableAI(e, t, n) {
        let r = n.substring(0, e), i;
        n.length < e + t ? i = n.length : i = e + t;
        let s = n.substring(e, i), o = n.substring(i), a = "(" + r + ")" + s, l = G.parseFieldsInGeneralPurpose(o);
        return l == null ? a : a + l;
      }
    }
    G.VARIABLE_LENGTH = [], G.TWO_DIGIT_DATA_LENGTH = [
      ["00", 18],
      ["01", 14],
      ["02", 14],
      ["10", G.VARIABLE_LENGTH, 20],
      ["11", 6],
      ["12", 6],
      ["13", 6],
      ["15", 6],
      ["17", 6],
      ["20", 2],
      ["21", G.VARIABLE_LENGTH, 20],
      ["22", G.VARIABLE_LENGTH, 29],
      ["30", G.VARIABLE_LENGTH, 8],
      ["37", G.VARIABLE_LENGTH, 8],
      // internal company codes
      ["90", G.VARIABLE_LENGTH, 30],
      ["91", G.VARIABLE_LENGTH, 30],
      ["92", G.VARIABLE_LENGTH, 30],
      ["93", G.VARIABLE_LENGTH, 30],
      ["94", G.VARIABLE_LENGTH, 30],
      ["95", G.VARIABLE_LENGTH, 30],
      ["96", G.VARIABLE_LENGTH, 30],
      ["97", G.VARIABLE_LENGTH, 3],
      ["98", G.VARIABLE_LENGTH, 30],
      ["99", G.VARIABLE_LENGTH, 30]
    ], G.THREE_DIGIT_DATA_LENGTH = [
      // Same format as above
      ["240", G.VARIABLE_LENGTH, 30],
      ["241", G.VARIABLE_LENGTH, 30],
      ["242", G.VARIABLE_LENGTH, 6],
      ["250", G.VARIABLE_LENGTH, 30],
      ["251", G.VARIABLE_LENGTH, 30],
      ["253", G.VARIABLE_LENGTH, 17],
      ["254", G.VARIABLE_LENGTH, 20],
      ["400", G.VARIABLE_LENGTH, 30],
      ["401", G.VARIABLE_LENGTH, 30],
      ["402", 17],
      ["403", G.VARIABLE_LENGTH, 30],
      ["410", 13],
      ["411", 13],
      ["412", 13],
      ["413", 13],
      ["414", 13],
      ["420", G.VARIABLE_LENGTH, 20],
      ["421", G.VARIABLE_LENGTH, 15],
      ["422", 3],
      ["423", G.VARIABLE_LENGTH, 15],
      ["424", 3],
      ["425", 3],
      ["426", 3]
    ], G.THREE_DIGIT_PLUS_DIGIT_DATA_LENGTH = [
      // Same format as above
      ["310", 6],
      ["311", 6],
      ["312", 6],
      ["313", 6],
      ["314", 6],
      ["315", 6],
      ["316", 6],
      ["320", 6],
      ["321", 6],
      ["322", 6],
      ["323", 6],
      ["324", 6],
      ["325", 6],
      ["326", 6],
      ["327", 6],
      ["328", 6],
      ["329", 6],
      ["330", 6],
      ["331", 6],
      ["332", 6],
      ["333", 6],
      ["334", 6],
      ["335", 6],
      ["336", 6],
      ["340", 6],
      ["341", 6],
      ["342", 6],
      ["343", 6],
      ["344", 6],
      ["345", 6],
      ["346", 6],
      ["347", 6],
      ["348", 6],
      ["349", 6],
      ["350", 6],
      ["351", 6],
      ["352", 6],
      ["353", 6],
      ["354", 6],
      ["355", 6],
      ["356", 6],
      ["357", 6],
      ["360", 6],
      ["361", 6],
      ["362", 6],
      ["363", 6],
      ["364", 6],
      ["365", 6],
      ["366", 6],
      ["367", 6],
      ["368", 6],
      ["369", 6],
      ["390", G.VARIABLE_LENGTH, 15],
      ["391", G.VARIABLE_LENGTH, 18],
      ["392", G.VARIABLE_LENGTH, 15],
      ["393", G.VARIABLE_LENGTH, 18],
      ["703", G.VARIABLE_LENGTH, 30]
    ], G.FOUR_DIGIT_DATA_LENGTH = [
      // Same format as above
      ["7001", 13],
      ["7002", G.VARIABLE_LENGTH, 30],
      ["7003", 10],
      ["8001", 14],
      ["8002", G.VARIABLE_LENGTH, 20],
      ["8003", G.VARIABLE_LENGTH, 30],
      ["8004", G.VARIABLE_LENGTH, 30],
      ["8005", 6],
      ["8006", 18],
      ["8007", G.VARIABLE_LENGTH, 30],
      ["8008", G.VARIABLE_LENGTH, 12],
      ["8018", 18],
      ["8020", G.VARIABLE_LENGTH, 25],
      ["8100", 6],
      ["8101", 10],
      ["8102", 2],
      ["8110", G.VARIABLE_LENGTH, 70],
      ["8200", G.VARIABLE_LENGTH, 70]
    ];
    class zt {
      constructor(e) {
        this.buffer = new ge(), this.information = e;
      }
      decodeAllCodes(e, t) {
        let n = t, r = null;
        do {
          let i = this.decodeGeneralPurposeField(n, r), s = G.parseFieldsInGeneralPurpose(i.getNewString());
          if (s != null && e.append(s), i.isRemaining() ? r = "" + i.getRemainingValue() : r = null, n === i.getNewPosition())
            break;
          n = i.getNewPosition();
        } while (!0);
        return e.toString();
      }
      isStillNumeric(e) {
        if (e + 7 > this.information.getSize())
          return e + 4 <= this.information.getSize();
        for (let t = e; t < e + 3; ++t)
          if (this.information.get(t))
            return !0;
        return this.information.get(e + 3);
      }
      decodeNumeric(e) {
        if (e + 7 > this.information.getSize()) {
          let i = this.extractNumericValueFromBitArray(e, 4);
          return i === 0 ? new at(this.information.getSize(), at.FNC1, at.FNC1) : new at(this.information.getSize(), i - 1, at.FNC1);
        }
        let t = this.extractNumericValueFromBitArray(e, 7), n = (t - 8) / 11, r = (t - 8) % 11;
        return new at(e + 7, n, r);
      }
      extractNumericValueFromBitArray(e, t) {
        return zt.extractNumericValueFromBitArray(this.information, e, t);
      }
      static extractNumericValueFromBitArray(e, t, n) {
        let r = 0;
        for (let i = 0; i < n; ++i)
          e.get(t + i) && (r |= 1 << n - i - 1);
        return r;
      }
      decodeGeneralPurposeField(e, t) {
        this.buffer.setLengthToZero(), t != null && this.buffer.append(t), this.current.setPosition(e);
        let n = this.parseBlocks();
        return n != null && n.isRemaining() ? new kt(this.current.getPosition(), this.buffer.toString(), n.getRemainingValue()) : new kt(this.current.getPosition(), this.buffer.toString());
      }
      parseBlocks() {
        let e, t;
        do {
          let n = this.current.getPosition();
          if (this.current.isAlpha() ? (t = this.parseAlphaBlock(), e = t.isFinished()) : this.current.isIsoIec646() ? (t = this.parseIsoIec646Block(), e = t.isFinished()) : (t = this.parseNumericBlock(), e = t.isFinished()), !(n !== this.current.getPosition()) && !e)
            break;
        } while (!e);
        return t.getDecodedInformation();
      }
      parseNumericBlock() {
        for (; this.isStillNumeric(this.current.getPosition()); ) {
          let e = this.decodeNumeric(this.current.getPosition());
          if (this.current.setPosition(e.getNewPosition()), e.isFirstDigitFNC1()) {
            let t;
            return e.isSecondDigitFNC1() ? t = new kt(this.current.getPosition(), this.buffer.toString()) : t = new kt(this.current.getPosition(), this.buffer.toString(), e.getSecondDigit()), new Ft(!0, t);
          }
          if (this.buffer.append(e.getFirstDigit()), e.isSecondDigitFNC1()) {
            let t = new kt(this.current.getPosition(), this.buffer.toString());
            return new Ft(!0, t);
          }
          this.buffer.append(e.getSecondDigit());
        }
        return this.isNumericToAlphaNumericLatch(this.current.getPosition()) && (this.current.setAlpha(), this.current.incrementPosition(4)), new Ft(!1);
      }
      parseIsoIec646Block() {
        for (; this.isStillIsoIec646(this.current.getPosition()); ) {
          let e = this.decodeIsoIec646(this.current.getPosition());
          if (this.current.setPosition(e.getNewPosition()), e.isFNC1()) {
            let t = new kt(this.current.getPosition(), this.buffer.toString());
            return new Ft(!0, t);
          }
          this.buffer.append(e.getValue());
        }
        return this.isAlphaOr646ToNumericLatch(this.current.getPosition()) ? (this.current.incrementPosition(3), this.current.setNumeric()) : this.isAlphaTo646ToAlphaLatch(this.current.getPosition()) && (this.current.getPosition() + 5 < this.information.getSize() ? this.current.incrementPosition(5) : this.current.setPosition(this.information.getSize()), this.current.setAlpha()), new Ft(!1);
      }
      parseAlphaBlock() {
        for (; this.isStillAlpha(this.current.getPosition()); ) {
          let e = this.decodeAlphanumeric(this.current.getPosition());
          if (this.current.setPosition(e.getNewPosition()), e.isFNC1()) {
            let t = new kt(this.current.getPosition(), this.buffer.toString());
            return new Ft(!0, t);
          }
          this.buffer.append(e.getValue());
        }
        return this.isAlphaOr646ToNumericLatch(this.current.getPosition()) ? (this.current.incrementPosition(3), this.current.setNumeric()) : this.isAlphaTo646ToAlphaLatch(this.current.getPosition()) && (this.current.getPosition() + 5 < this.information.getSize() ? this.current.incrementPosition(5) : this.current.setPosition(this.information.getSize()), this.current.setIsoIec646()), new Ft(!1);
      }
      isStillIsoIec646(e) {
        if (e + 5 > this.information.getSize())
          return !1;
        let t = this.extractNumericValueFromBitArray(e, 5);
        if (t >= 5 && t < 16)
          return !0;
        if (e + 7 > this.information.getSize())
          return !1;
        let n = this.extractNumericValueFromBitArray(e, 7);
        if (n >= 64 && n < 116)
          return !0;
        if (e + 8 > this.information.getSize())
          return !1;
        let r = this.extractNumericValueFromBitArray(e, 8);
        return r >= 232 && r < 253;
      }
      decodeIsoIec646(e) {
        let t = this.extractNumericValueFromBitArray(e, 5);
        if (t === 15)
          return new et(e + 5, et.FNC1);
        if (t >= 5 && t < 15)
          return new et(e + 5, "0" + (t - 5));
        let n = this.extractNumericValueFromBitArray(e, 7);
        if (n >= 64 && n < 90)
          return new et(e + 7, "" + (n + 1));
        if (n >= 90 && n < 116)
          return new et(e + 7, "" + (n + 7));
        let r = this.extractNumericValueFromBitArray(e, 8), i;
        switch (r) {
          case 232:
            i = "!";
            break;
          case 233:
            i = '"';
            break;
          case 234:
            i = "%";
            break;
          case 235:
            i = "&";
            break;
          case 236:
            i = "'";
            break;
          case 237:
            i = "(";
            break;
          case 238:
            i = ")";
            break;
          case 239:
            i = "*";
            break;
          case 240:
            i = "+";
            break;
          case 241:
            i = ",";
            break;
          case 242:
            i = "-";
            break;
          case 243:
            i = ".";
            break;
          case 244:
            i = "/";
            break;
          case 245:
            i = ":";
            break;
          case 246:
            i = ";";
            break;
          case 247:
            i = "<";
            break;
          case 248:
            i = "=";
            break;
          case 249:
            i = ">";
            break;
          case 250:
            i = "?";
            break;
          case 251:
            i = "_";
            break;
          case 252:
            i = " ";
            break;
          default:
            throw new U();
        }
        return new et(e + 8, i);
      }
      isStillAlpha(e) {
        if (e + 5 > this.information.getSize())
          return !1;
        let t = this.extractNumericValueFromBitArray(e, 5);
        if (t >= 5 && t < 16)
          return !0;
        if (e + 6 > this.information.getSize())
          return !1;
        let n = this.extractNumericValueFromBitArray(e, 6);
        return n >= 16 && n < 63;
      }
      decodeAlphanumeric(e) {
        let t = this.extractNumericValueFromBitArray(e, 5);
        if (t === 15)
          return new et(e + 5, et.FNC1);
        if (t >= 5 && t < 15)
          return new et(e + 5, "0" + (t - 5));
        let n = this.extractNumericValueFromBitArray(e, 6);
        if (n >= 32 && n < 58)
          return new et(e + 6, "" + (n + 33));
        let r;
        switch (n) {
          case 58:
            r = "*";
            break;
          case 59:
            r = ",";
            break;
          case 60:
            r = "-";
            break;
          case 61:
            r = ".";
            break;
          case 62:
            r = "/";
            break;
          default:
            throw new gt("Decoding invalid alphanumeric value: " + n);
        }
        return new et(e + 6, r);
      }
      isAlphaTo646ToAlphaLatch(e) {
        if (e + 1 > this.information.getSize())
          return !1;
        for (let t = 0; t < 5 && t + e < this.information.getSize(); ++t)
          if (t === 2) {
            if (!this.information.get(e + 2))
              return !1;
          } else if (this.information.get(e + t))
            return !1;
        return !0;
      }
      isAlphaOr646ToNumericLatch(e) {
        if (e + 3 > this.information.getSize())
          return !1;
        for (let t = e; t < e + 3; ++t)
          if (this.information.get(t))
            return !1;
        return !0;
      }
      isNumericToAlphaNumericLatch(e) {
        if (e + 1 > this.information.getSize())
          return !1;
        for (let t = 0; t < 4 && t + e < this.information.getSize(); ++t)
          if (this.information.get(e + t))
            return !1;
        return !0;
      }
    }
    class Vn {
      constructor(e) {
        this.information = e, this.generalDecoder = new zt(e);
      }
      getInformation() {
        return this.information;
      }
      getGeneralDecoder() {
        return this.generalDecoder;
      }
    }
    class tt extends Vn {
      constructor(e) {
        super(e);
      }
      encodeCompressedGtin(e, t) {
        e.append("(01)");
        let n = e.length();
        e.append("9"), this.encodeCompressedGtinWithoutAI(e, t, n);
      }
      encodeCompressedGtinWithoutAI(e, t, n) {
        for (let r = 0; r < 4; ++r) {
          let i = this.getGeneralDecoder().extractNumericValueFromBitArray(t + 10 * r, 10);
          i / 100 === 0 && e.append("0"), i / 10 === 0 && e.append("0"), e.append(i);
        }
        tt.appendCheckDigit(e, n);
      }
      static appendCheckDigit(e, t) {
        let n = 0;
        for (let r = 0; r < 13; r++) {
          let i = e.charAt(r + t).charCodeAt(0) - 48;
          n += r & 1 ? i : 3 * i;
        }
        n = 10 - n % 10, n === 10 && (n = 0), e.append(n);
      }
    }
    tt.GTIN_SIZE = 40;
    class Zt extends tt {
      // the second one is the encodation method, and the other two are for the variable length
      constructor(e) {
        super(e);
      }
      parseInformation() {
        let e = new ge();
        e.append("(01)");
        let t = e.length(), n = this.getGeneralDecoder().extractNumericValueFromBitArray(Zt.HEADER_SIZE, 4);
        return e.append(n), this.encodeCompressedGtinWithoutAI(e, Zt.HEADER_SIZE + 4, t), this.getGeneralDecoder().decodeAllCodes(e, Zt.HEADER_SIZE + 44);
      }
    }
    Zt.HEADER_SIZE = 4;
    class pn extends Vn {
      constructor(e) {
        super(e);
      }
      parseInformation() {
        let e = new ge();
        return this.getGeneralDecoder().decodeAllCodes(e, pn.HEADER_SIZE);
      }
    }
    pn.HEADER_SIZE = 5;
    class bn extends tt {
      constructor(e) {
        super(e);
      }
      encodeCompressedWeight(e, t, n) {
        let r = this.getGeneralDecoder().extractNumericValueFromBitArray(t, n);
        this.addWeightCode(e, r);
        let i = this.checkWeight(r), s = 1e5;
        for (let o = 0; o < 5; ++o)
          i / s === 0 && e.append("0"), s /= 10;
        e.append(i);
      }
    }
    class At extends bn {
      constructor(e) {
        super(e);
      }
      parseInformation() {
        if (this.getInformation().getSize() != At.HEADER_SIZE + bn.GTIN_SIZE + At.WEIGHT_SIZE)
          throw new O();
        let e = new ge();
        return this.encodeCompressedGtin(e, At.HEADER_SIZE), this.encodeCompressedWeight(e, At.HEADER_SIZE + bn.GTIN_SIZE, At.WEIGHT_SIZE), e.toString();
      }
    }
    At.HEADER_SIZE = 5, At.WEIGHT_SIZE = 15;
    class Xr extends At {
      constructor(e) {
        super(e);
      }
      addWeightCode(e, t) {
        e.append("(3103)");
      }
      checkWeight(e) {
        return e;
      }
    }
    class Yr extends At {
      constructor(e) {
        super(e);
      }
      addWeightCode(e, t) {
        t < 1e4 ? e.append("(3202)") : e.append("(3203)");
      }
      checkWeight(e) {
        return e < 1e4 ? e : e - 1e4;
      }
    }
    class Et extends tt {
      constructor(e) {
        super(e);
      }
      parseInformation() {
        if (this.getInformation().getSize() < Et.HEADER_SIZE + tt.GTIN_SIZE)
          throw new O();
        let e = new ge();
        this.encodeCompressedGtin(e, Et.HEADER_SIZE);
        let t = this.getGeneralDecoder().extractNumericValueFromBitArray(Et.HEADER_SIZE + tt.GTIN_SIZE, Et.LAST_DIGIT_SIZE);
        e.append("(392"), e.append(t), e.append(")");
        let n = this.getGeneralDecoder().decodeGeneralPurposeField(Et.HEADER_SIZE + tt.GTIN_SIZE + Et.LAST_DIGIT_SIZE, null);
        return e.append(n.getNewString()), e.toString();
      }
    }
    Et.HEADER_SIZE = 8, Et.LAST_DIGIT_SIZE = 2;
    class Qe extends tt {
      constructor(e) {
        super(e);
      }
      parseInformation() {
        if (this.getInformation().getSize() < Qe.HEADER_SIZE + tt.GTIN_SIZE)
          throw new O();
        let e = new ge();
        this.encodeCompressedGtin(e, Qe.HEADER_SIZE);
        let t = this.getGeneralDecoder().extractNumericValueFromBitArray(Qe.HEADER_SIZE + tt.GTIN_SIZE, Qe.LAST_DIGIT_SIZE);
        e.append("(393"), e.append(t), e.append(")");
        let n = this.getGeneralDecoder().extractNumericValueFromBitArray(Qe.HEADER_SIZE + tt.GTIN_SIZE + Qe.LAST_DIGIT_SIZE, Qe.FIRST_THREE_DIGITS_SIZE);
        n / 100 == 0 && e.append("0"), n / 10 == 0 && e.append("0"), e.append(n);
        let r = this.getGeneralDecoder().decodeGeneralPurposeField(Qe.HEADER_SIZE + tt.GTIN_SIZE + Qe.LAST_DIGIT_SIZE + Qe.FIRST_THREE_DIGITS_SIZE, null);
        return e.append(r.getNewString()), e.toString();
      }
    }
    Qe.HEADER_SIZE = 8, Qe.LAST_DIGIT_SIZE = 2, Qe.FIRST_THREE_DIGITS_SIZE = 10;
    class ye extends bn {
      constructor(e, t, n) {
        super(e), this.dateCode = n, this.firstAIdigits = t;
      }
      parseInformation() {
        if (this.getInformation().getSize() != ye.HEADER_SIZE + ye.GTIN_SIZE + ye.WEIGHT_SIZE + ye.DATE_SIZE)
          throw new O();
        let e = new ge();
        return this.encodeCompressedGtin(e, ye.HEADER_SIZE), this.encodeCompressedWeight(e, ye.HEADER_SIZE + ye.GTIN_SIZE, ye.WEIGHT_SIZE), this.encodeCompressedDate(e, ye.HEADER_SIZE + ye.GTIN_SIZE + ye.WEIGHT_SIZE), e.toString();
      }
      encodeCompressedDate(e, t) {
        let n = this.getGeneralDecoder().extractNumericValueFromBitArray(t, ye.DATE_SIZE);
        if (n == 38400)
          return;
        e.append("("), e.append(this.dateCode), e.append(")");
        let r = n % 32;
        n /= 32;
        let i = n % 12 + 1;
        n /= 12;
        let s = n;
        s / 10 == 0 && e.append("0"), e.append(s), i / 10 == 0 && e.append("0"), e.append(i), r / 10 == 0 && e.append("0"), e.append(r);
      }
      addWeightCode(e, t) {
        e.append("("), e.append(this.firstAIdigits), e.append(t / 1e5), e.append(")");
      }
      checkWeight(e) {
        return e % 1e5;
      }
    }
    ye.HEADER_SIZE = 8, ye.WEIGHT_SIZE = 20, ye.DATE_SIZE = 16;
    function fr(u) {
      try {
        if (u.get(1))
          return new Zt(u);
        if (!u.get(2))
          return new pn(u);
        switch (zt.extractNumericValueFromBitArray(u, 1, 4)) {
          case 4:
            return new Xr(u);
          case 5:
            return new Yr(u);
        }
        switch (zt.extractNumericValueFromBitArray(u, 1, 5)) {
          case 12:
            return new Et(u);
          case 13:
            return new Qe(u);
        }
        switch (zt.extractNumericValueFromBitArray(u, 1, 7)) {
          case 56:
            return new ye(u, "310", "11");
          case 57:
            return new ye(u, "320", "11");
          case 58:
            return new ye(u, "310", "13");
          case 59:
            return new ye(u, "320", "13");
          case 60:
            return new ye(u, "310", "15");
          case 61:
            return new ye(u, "320", "15");
          case 62:
            return new ye(u, "310", "17");
          case 63:
            return new ye(u, "320", "17");
        }
      } catch (e) {
        throw console.log(e), new gt("unknown decoder: " + u);
      }
    }
    class Ot {
      constructor(e, t, n, r) {
        this.leftchar = e, this.rightchar = t, this.finderpattern = n, this.maybeLast = r;
      }
      mayBeLast() {
        return this.maybeLast;
      }
      getLeftChar() {
        return this.leftchar;
      }
      getRightChar() {
        return this.rightchar;
      }
      getFinderPattern() {
        return this.finderpattern;
      }
      mustBeLast() {
        return this.rightchar == null;
      }
      toString() {
        return "[ " + this.leftchar + ", " + this.rightchar + " : " + (this.finderpattern == null ? "null" : this.finderpattern.getValue()) + " ]";
      }
      static equals(e, t) {
        return e instanceof Ot ? Ot.equalsOrNull(e.leftchar, t.leftchar) && Ot.equalsOrNull(e.rightchar, t.rightchar) && Ot.equalsOrNull(e.finderpattern, t.finderpattern) : !1;
      }
      static equalsOrNull(e, t) {
        return e === null ? t === null : Ot.equals(e, t);
      }
      hashCode() {
        return this.leftchar.getValue() ^ this.rightchar.getValue() ^ this.finderpattern.getValue();
      }
    }
    class Gn {
      constructor(e, t, n) {
        this.pairs = e, this.rowNumber = t, this.wasReversed = n;
      }
      getPairs() {
        return this.pairs;
      }
      getRowNumber() {
        return this.rowNumber;
      }
      isReversed() {
        return this.wasReversed;
      }
      // check implementation
      isEquivalent(e) {
        return this.checkEqualitity(this, e);
      }
      // @Override
      toString() {
        return "{ " + this.pairs + " }";
      }
      /**
       * Two rows are equal if they contain the same pairs in the same order.
       */
      // @Override
      // check implementation
      equals(e, t) {
        return e instanceof Gn ? this.checkEqualitity(e, t) && e.wasReversed === t.wasReversed : !1;
      }
      checkEqualitity(e, t) {
        if (!e || !t)
          return;
        let n;
        return e.forEach((r, i) => {
          t.forEach((s) => {
            r.getLeftChar().getValue() === s.getLeftChar().getValue() && r.getRightChar().getValue() === s.getRightChar().getValue() && r.getFinderPatter().getValue() === s.getFinderPatter().getValue() && (n = !0);
          });
        }), n;
      }
    }
    class v extends ze {
      constructor(e) {
        super(...arguments), this.pairs = new Array(v.MAX_PAIRS), this.rows = new Array(), this.startEnd = [2], this.verbose = e === !0;
      }
      decodeRow(e, t, n) {
        this.pairs.length = 0, this.startFromEven = !1;
        try {
          return v.constructResult(this.decodeRow2pairs(e, t));
        } catch (r) {
          this.verbose && console.log(r);
        }
        return this.pairs.length = 0, this.startFromEven = !0, v.constructResult(this.decodeRow2pairs(e, t));
      }
      reset() {
        this.pairs.length = 0, this.rows.length = 0;
      }
      // Not private for testing
      decodeRow2pairs(e, t) {
        let n = !1;
        for (; !n; )
          try {
            this.pairs.push(this.retrieveNextPair(t, this.pairs, e));
          } catch (i) {
            if (i instanceof O) {
              if (!this.pairs.length)
                throw new O();
              n = !0;
            }
          }
        if (this.checkChecksum())
          return this.pairs;
        let r;
        if (this.rows.length ? r = !0 : r = !1, this.storeRow(e, !1), r) {
          let i = this.checkRowsBoolean(!1);
          if (i != null || (i = this.checkRowsBoolean(!0), i != null))
            return i;
        }
        throw new O();
      }
      // Need to Verify
      checkRowsBoolean(e) {
        if (this.rows.length > 25)
          return this.rows.length = 0, null;
        this.pairs.length = 0, e && (this.rows = this.rows.reverse());
        let t = null;
        try {
          t = this.checkRows(new Array(), 0);
        } catch (n) {
          this.verbose && console.log(n);
        }
        return e && (this.rows = this.rows.reverse()), t;
      }
      // Try to construct a valid rows sequence
      // Recursion is used to implement backtracking
      checkRows(e, t) {
        for (let n = t; n < this.rows.length; n++) {
          let r = this.rows[n];
          this.pairs.length = 0;
          for (let s of e)
            this.pairs.push(s.getPairs());
          if (this.pairs.push(r.getPairs()), !v.isValidSequence(this.pairs))
            continue;
          if (this.checkChecksum())
            return this.pairs;
          let i = new Array(e);
          i.push(r);
          try {
            return this.checkRows(i, n + 1);
          } catch (s) {
            this.verbose && console.log(s);
          }
        }
        throw new O();
      }
      // Whether the pairs form a valid find pattern sequence,
      // either complete or a prefix
      static isValidSequence(e) {
        for (let t of v.FINDER_PATTERN_SEQUENCES) {
          if (e.length > t.length)
            continue;
          let n = !0;
          for (let r = 0; r < e.length; r++)
            if (e[r].getFinderPattern().getValue() != t[r]) {
              n = !1;
              break;
            }
          if (n)
            return !0;
        }
        return !1;
      }
      storeRow(e, t) {
        let n = 0, r = !1, i = !1;
        for (; n < this.rows.length; ) {
          let s = this.rows[n];
          if (s.getRowNumber() > e) {
            i = s.isEquivalent(this.pairs);
            break;
          }
          r = s.isEquivalent(this.pairs), n++;
        }
        i || r || v.isPartialRow(this.pairs, this.rows) || (this.rows.push(n, new Gn(this.pairs, e, t)), this.removePartialRows(this.pairs, this.rows));
      }
      // Remove all the rows that contains only specified pairs
      removePartialRows(e, t) {
        for (let n of t)
          if (n.getPairs().length !== e.length) {
            for (let r of n.getPairs())
              for (let i of e)
                if (Ot.equals(r, i))
                  break;
          }
      }
      // Returns true when one of the rows already contains all the pairs
      static isPartialRow(e, t) {
        for (let n of t) {
          let r = !0;
          for (let i of e) {
            let s = !1;
            for (let o of n.getPairs())
              if (i.equals(o)) {
                s = !0;
                break;
              }
            if (!s) {
              r = !1;
              break;
            }
          }
          if (r)
            return !0;
        }
        return !1;
      }
      // Only used for unit testing
      getRows() {
        return this.rows;
      }
      // Not private for unit testing
      static constructResult(e) {
        let t = Wr.buildBitArray(e), r = fr(t).parseInformation(), i = e[0].getFinderPattern().getResultPoints(), s = e[e.length - 1].getFinderPattern().getResultPoints(), o = [i[0], i[1], s[0], s[1]];
        return new je(r, null, null, o, z.RSS_EXPANDED, null);
      }
      checkChecksum() {
        let e = this.pairs.get(0), t = e.getLeftChar(), n = e.getRightChar();
        if (n == null)
          return !1;
        let r = n.getChecksumPortion(), i = 2;
        for (let o = 1; o < this.pairs.size(); ++o) {
          let a = this.pairs.get(o);
          r += a.getLeftChar().getChecksumPortion(), i++;
          let l = a.getRightChar();
          l != null && (r += l.getChecksumPortion(), i++);
        }
        return r %= 211, 211 * (i - 4) + r == t.getValue();
      }
      static getNextSecondBar(e, t) {
        let n;
        return e.get(t) ? (n = e.getNextUnset(t), n = e.getNextSet(n)) : (n = e.getNextSet(t), n = e.getNextUnset(n)), n;
      }
      // not private for testing
      retrieveNextPair(e, t, n) {
        let r = t.length % 2 == 0;
        this.startFromEven && (r = !r);
        let i, s = !0, o = -1;
        do
          this.findNextPair(e, t, o), i = this.parseFoundFinderPattern(e, n, r), i == null ? o = v.getNextSecondBar(e, this.startEnd[0]) : s = !1;
        while (s);
        let a = this.decodeDataCharacter(e, i, r, !0);
        if (!this.isEmptyPair(t) && t[t.length - 1].mustBeLast())
          throw new O();
        let l;
        try {
          l = this.decodeDataCharacter(e, i, r, !1);
        } catch (c) {
          l = null, this.verbose && console.log(c);
        }
        return new Ot(a, l, i, !0);
      }
      isEmptyPair(e) {
        return e.length === 0;
      }
      findNextPair(e, t, n) {
        let r = this.getDecodeFinderCounters();
        r[0] = 0, r[1] = 0, r[2] = 0, r[3] = 0;
        let i = e.getSize(), s;
        n >= 0 ? s = n : this.isEmptyPair(t) ? s = 0 : s = t[t.length - 1].getFinderPattern().getStartEnd()[1];
        let o = t.length % 2 != 0;
        this.startFromEven && (o = !o);
        let a = !1;
        for (; s < i && (a = !e.get(s), !!a); )
          s++;
        let l = 0, c = s;
        for (let f = s; f < i; f++)
          if (e.get(f) != a)
            r[l]++;
          else {
            if (l == 3) {
              if (o && v.reverseCounters(r), v.isFinderPattern(r)) {
                this.startEnd[0] = c, this.startEnd[1] = f;
                return;
              }
              o && v.reverseCounters(r), c += r[0] + r[1], r[0] = r[2], r[1] = r[3], r[2] = 0, r[3] = 0, l--;
            } else
              l++;
            r[l] = 1, a = !a;
          }
        throw new O();
      }
      static reverseCounters(e) {
        let t = e.length;
        for (let n = 0; n < t / 2; ++n) {
          let r = e[n];
          e[n] = e[t - n - 1], e[t - n - 1] = r;
        }
      }
      parseFoundFinderPattern(e, t, n) {
        let r, i, s;
        if (n) {
          let l = this.startEnd[0] - 1;
          for (; l >= 0 && !e.get(l); )
            l--;
          l++, r = this.startEnd[0] - l, i = l, s = this.startEnd[1];
        } else
          i = this.startEnd[0], s = e.getNextUnset(this.startEnd[1] + 1), r = s - this.startEnd[1];
        let o = this.getDecodeFinderCounters();
        re.arraycopy(o, 0, o, 1, o.length - 1), o[0] = r;
        let a;
        try {
          a = this.parseFinderValue(o, v.FINDER_PATTERNS);
        } catch {
          return null;
        }
        return new In(a, [i, s], i, s, t);
      }
      decodeDataCharacter(e, t, n, r) {
        let i = this.getDataCharacterCounters();
        for (let Ce = 0; Ce < i.length; Ce++)
          i[Ce] = 0;
        if (r)
          v.recordPatternInReverse(e, t.getStartEnd()[0], i);
        else {
          v.recordPattern(e, t.getStartEnd()[1], i);
          for (let Ce = 0, Ye = i.length - 1; Ce < Ye; Ce++, Ye--) {
            let st = i[Ce];
            i[Ce] = i[Ye], i[Ye] = st;
          }
        }
        let s = 17, o = oe.sum(new Int32Array(i)) / s, a = (t.getStartEnd()[1] - t.getStartEnd()[0]) / 15;
        if (Math.abs(o - a) / a > 0.3)
          throw new O();
        let l = this.getOddCounts(), c = this.getEvenCounts(), f = this.getOddRoundingErrors(), d = this.getEvenRoundingErrors();
        for (let Ce = 0; Ce < i.length; Ce++) {
          let Ye = 1 * i[Ce] / o, st = Ye + 0.5;
          if (st < 1) {
            if (Ye < 0.3)
              throw new O();
            st = 1;
          } else if (st > 8) {
            if (Ye > 8.7)
              throw new O();
            st = 8;
          }
          let Jt = Ce / 2;
          Ce & 1 ? (c[Jt] = st, d[Jt] = Ye - st) : (l[Jt] = st, f[Jt] = Ye - st);
        }
        this.adjustOddEvenCounts(s);
        let C = 4 * t.getValue() + (n ? 0 : 2) + (r ? 0 : 1) - 1, p = 0, b = 0;
        for (let Ce = l.length - 1; Ce >= 0; Ce--) {
          if (v.isNotA1left(t, n, r)) {
            let Ye = v.WEIGHTS[C][2 * Ce];
            b += l[Ce] * Ye;
          }
          p += l[Ce];
        }
        let y = 0;
        for (let Ce = c.length - 1; Ce >= 0; Ce--)
          if (v.isNotA1left(t, n, r)) {
            let Ye = v.WEIGHTS[C][2 * Ce + 1];
            y += c[Ce] * Ye;
          }
        let _ = b + y;
        if (p & 1 || p > 13 || p < 4)
          throw new O();
        let P = (13 - p) / 2, F = v.SYMBOL_WIDEST[P], L = 9 - F, ie = wt.getRSSvalue(l, F, !0), Q = wt.getRSSvalue(c, L, !1), rt = v.EVEN_TOTAL_SUBSET[P], dt = v.GSUM[P], it = ie * rt + Q + dt;
        return new Yt(it, _);
      }
      static isNotA1left(e, t, n) {
        return !(e.getValue() == 0 && t && n);
      }
      adjustOddEvenCounts(e) {
        let t = oe.sum(new Int32Array(this.getOddCounts())), n = oe.sum(new Int32Array(this.getEvenCounts())), r = !1, i = !1;
        t > 13 ? i = !0 : t < 4 && (r = !0);
        let s = !1, o = !1;
        n > 13 ? o = !0 : n < 4 && (s = !0);
        let a = t + n - e, l = (t & 1) == 1, c = (n & 1) == 0;
        if (a == 1)
          if (l) {
            if (c)
              throw new O();
            i = !0;
          } else {
            if (!c)
              throw new O();
            o = !0;
          }
        else if (a == -1)
          if (l) {
            if (c)
              throw new O();
            r = !0;
          } else {
            if (!c)
              throw new O();
            s = !0;
          }
        else if (a == 0) {
          if (l) {
            if (!c)
              throw new O();
            t < n ? (r = !0, o = !0) : (i = !0, s = !0);
          } else if (c)
            throw new O();
        } else
          throw new O();
        if (r) {
          if (i)
            throw new O();
          v.increment(this.getOddCounts(), this.getOddRoundingErrors());
        }
        if (i && v.decrement(this.getOddCounts(), this.getOddRoundingErrors()), s) {
          if (o)
            throw new O();
          v.increment(this.getEvenCounts(), this.getOddRoundingErrors());
        }
        o && v.decrement(this.getEvenCounts(), this.getEvenRoundingErrors());
      }
    }
    v.SYMBOL_WIDEST = [7, 5, 4, 3, 1], v.EVEN_TOTAL_SUBSET = [4, 20, 52, 104, 204], v.GSUM = [0, 348, 1388, 2948, 3988], v.FINDER_PATTERNS = [
      Int32Array.from([1, 8, 4, 1]),
      Int32Array.from([3, 6, 4, 1]),
      Int32Array.from([3, 4, 6, 1]),
      Int32Array.from([3, 2, 8, 1]),
      Int32Array.from([2, 6, 5, 1]),
      Int32Array.from([2, 2, 9, 1])
      // F
    ], v.WEIGHTS = [
      [1, 3, 9, 27, 81, 32, 96, 77],
      [20, 60, 180, 118, 143, 7, 21, 63],
      [189, 145, 13, 39, 117, 140, 209, 205],
      [193, 157, 49, 147, 19, 57, 171, 91],
      [62, 186, 136, 197, 169, 85, 44, 132],
      [185, 133, 188, 142, 4, 12, 36, 108],
      [113, 128, 173, 97, 80, 29, 87, 50],
      [150, 28, 84, 41, 123, 158, 52, 156],
      [46, 138, 203, 187, 139, 206, 196, 166],
      [76, 17, 51, 153, 37, 111, 122, 155],
      [43, 129, 176, 106, 107, 110, 119, 146],
      [16, 48, 144, 10, 30, 90, 59, 177],
      [109, 116, 137, 200, 178, 112, 125, 164],
      [70, 210, 208, 202, 184, 130, 179, 115],
      [134, 191, 151, 31, 93, 68, 204, 190],
      [148, 22, 66, 198, 172, 94, 71, 2],
      [6, 18, 54, 162, 64, 192, 154, 40],
      [120, 149, 25, 75, 14, 42, 126, 167],
      [79, 26, 78, 23, 69, 207, 199, 175],
      [103, 98, 83, 38, 114, 131, 182, 124],
      [161, 61, 183, 127, 170, 88, 53, 159],
      [55, 165, 73, 8, 24, 72, 5, 15],
      [45, 135, 194, 160, 58, 174, 100, 89]
    ], v.FINDER_PAT_A = 0, v.FINDER_PAT_B = 1, v.FINDER_PAT_C = 2, v.FINDER_PAT_D = 3, v.FINDER_PAT_E = 4, v.FINDER_PAT_F = 5, v.FINDER_PATTERN_SEQUENCES = [
      [v.FINDER_PAT_A, v.FINDER_PAT_A],
      [v.FINDER_PAT_A, v.FINDER_PAT_B, v.FINDER_PAT_B],
      [v.FINDER_PAT_A, v.FINDER_PAT_C, v.FINDER_PAT_B, v.FINDER_PAT_D],
      [v.FINDER_PAT_A, v.FINDER_PAT_E, v.FINDER_PAT_B, v.FINDER_PAT_D, v.FINDER_PAT_C],
      [v.FINDER_PAT_A, v.FINDER_PAT_E, v.FINDER_PAT_B, v.FINDER_PAT_D, v.FINDER_PAT_D, v.FINDER_PAT_F],
      [v.FINDER_PAT_A, v.FINDER_PAT_E, v.FINDER_PAT_B, v.FINDER_PAT_D, v.FINDER_PAT_E, v.FINDER_PAT_F, v.FINDER_PAT_F],
      [v.FINDER_PAT_A, v.FINDER_PAT_A, v.FINDER_PAT_B, v.FINDER_PAT_B, v.FINDER_PAT_C, v.FINDER_PAT_C, v.FINDER_PAT_D, v.FINDER_PAT_D],
      [v.FINDER_PAT_A, v.FINDER_PAT_A, v.FINDER_PAT_B, v.FINDER_PAT_B, v.FINDER_PAT_C, v.FINDER_PAT_C, v.FINDER_PAT_D, v.FINDER_PAT_E, v.FINDER_PAT_E],
      [v.FINDER_PAT_A, v.FINDER_PAT_A, v.FINDER_PAT_B, v.FINDER_PAT_B, v.FINDER_PAT_C, v.FINDER_PAT_C, v.FINDER_PAT_D, v.FINDER_PAT_E, v.FINDER_PAT_F, v.FINDER_PAT_F],
      [v.FINDER_PAT_A, v.FINDER_PAT_A, v.FINDER_PAT_B, v.FINDER_PAT_B, v.FINDER_PAT_C, v.FINDER_PAT_D, v.FINDER_PAT_D, v.FINDER_PAT_E, v.FINDER_PAT_E, v.FINDER_PAT_F, v.FINDER_PAT_F]
    ], v.MAX_PAIRS = 11;
    class zr extends Yt {
      constructor(e, t, n) {
        super(e, t), this.count = 0, this.finderPattern = n;
      }
      getFinderPattern() {
        return this.finderPattern;
      }
      getCount() {
        return this.count;
      }
      incrementCount() {
        this.count++;
      }
    }
    class _e extends ze {
      constructor() {
        super(...arguments), this.possibleLeftPairs = [], this.possibleRightPairs = [];
      }
      decodeRow(e, t, n) {
        const r = this.decodePair(t, !1, e, n);
        _e.addOrTally(this.possibleLeftPairs, r), t.reverse();
        let i = this.decodePair(t, !0, e, n);
        _e.addOrTally(this.possibleRightPairs, i), t.reverse();
        for (let s of this.possibleLeftPairs)
          if (s.getCount() > 1) {
            for (let o of this.possibleRightPairs)
              if (o.getCount() > 1 && _e.checkChecksum(s, o))
                return _e.constructResult(s, o);
          }
        throw new O();
      }
      static addOrTally(e, t) {
        if (t == null)
          return;
        let n = !1;
        for (let r of e)
          if (r.getValue() === t.getValue()) {
            r.incrementCount(), n = !0;
            break;
          }
        n || e.push(t);
      }
      reset() {
        this.possibleLeftPairs.length = 0, this.possibleRightPairs.length = 0;
      }
      static constructResult(e, t) {
        let n = 4537077 * e.getValue() + t.getValue(), r = new String(n).toString(), i = new ge();
        for (let l = 13 - r.length; l > 0; l--)
          i.append("0");
        i.append(r);
        let s = 0;
        for (let l = 0; l < 13; l++) {
          let c = i.charAt(l).charCodeAt(0) - 48;
          s += l & 1 ? c : 3 * c;
        }
        s = 10 - s % 10, s === 10 && (s = 0), i.append(s.toString());
        let o = e.getFinderPattern().getResultPoints(), a = t.getFinderPattern().getResultPoints();
        return new je(i.toString(), null, 0, [o[0], o[1], a[0], a[1]], z.RSS_14, (/* @__PURE__ */ new Date()).getTime());
      }
      static checkChecksum(e, t) {
        let n = (e.getChecksumPortion() + 16 * t.getChecksumPortion()) % 79, r = 9 * e.getFinderPattern().getValue() + t.getFinderPattern().getValue();
        return r > 72 && r--, r > 8 && r--, n === r;
      }
      decodePair(e, t, n, r) {
        try {
          let i = this.findFinderPattern(e, t), s = this.parseFoundFinderPattern(e, n, t, i), o = r == null ? null : r.get(xe.NEED_RESULT_POINT_CALLBACK);
          if (o != null) {
            let c = (i[0] + i[1]) / 2;
            t && (c = e.getSize() - 1 - c), o.foundPossibleResultPoint(new W(c, n));
          }
          let a = this.decodeDataCharacter(e, s, !0), l = this.decodeDataCharacter(e, s, !1);
          return new zr(1597 * a.getValue() + l.getValue(), a.getChecksumPortion() + 4 * l.getChecksumPortion(), s);
        } catch {
          return null;
        }
      }
      decodeDataCharacter(e, t, n) {
        let r = this.getDataCharacterCounters();
        for (let y = 0; y < r.length; y++)
          r[y] = 0;
        if (n)
          Me.recordPatternInReverse(e, t.getStartEnd()[0], r);
        else {
          Me.recordPattern(e, t.getStartEnd()[1] + 1, r);
          for (let y = 0, _ = r.length - 1; y < _; y++, _--) {
            let P = r[y];
            r[y] = r[_], r[_] = P;
          }
        }
        let i = n ? 16 : 15, s = oe.sum(new Int32Array(r)) / i, o = this.getOddCounts(), a = this.getEvenCounts(), l = this.getOddRoundingErrors(), c = this.getEvenRoundingErrors();
        for (let y = 0; y < r.length; y++) {
          let _ = r[y] / s, P = Math.floor(_ + 0.5);
          P < 1 ? P = 1 : P > 8 && (P = 8);
          let F = Math.floor(y / 2);
          y & 1 ? (a[F] = P, c[F] = _ - P) : (o[F] = P, l[F] = _ - P);
        }
        this.adjustOddEvenCounts(n, i);
        let f = 0, d = 0;
        for (let y = o.length - 1; y >= 0; y--)
          d *= 9, d += o[y], f += o[y];
        let C = 0, p = 0;
        for (let y = a.length - 1; y >= 0; y--)
          C *= 9, C += a[y], p += a[y];
        let b = d + 3 * C;
        if (n) {
          if (f & 1 || f > 12 || f < 4)
            throw new O();
          let y = (12 - f) / 2, _ = _e.OUTSIDE_ODD_WIDEST[y], P = 9 - _, F = wt.getRSSvalue(o, _, !1), L = wt.getRSSvalue(a, P, !0), ie = _e.OUTSIDE_EVEN_TOTAL_SUBSET[y], Q = _e.OUTSIDE_GSUM[y];
          return new Yt(F * ie + L + Q, b);
        } else {
          if (p & 1 || p > 10 || p < 4)
            throw new O();
          let y = (10 - p) / 2, _ = _e.INSIDE_ODD_WIDEST[y], P = 9 - _, F = wt.getRSSvalue(o, _, !0), L = wt.getRSSvalue(a, P, !1), ie = _e.INSIDE_ODD_TOTAL_SUBSET[y], Q = _e.INSIDE_GSUM[y];
          return new Yt(L * ie + F + Q, b);
        }
      }
      findFinderPattern(e, t) {
        let n = this.getDecodeFinderCounters();
        n[0] = 0, n[1] = 0, n[2] = 0, n[3] = 0;
        let r = e.getSize(), i = !1, s = 0;
        for (; s < r && (i = !e.get(s), t !== i); )
          s++;
        let o = 0, a = s;
        for (let l = s; l < r; l++)
          if (e.get(l) !== i)
            n[o]++;
          else {
            if (o === 3) {
              if (ze.isFinderPattern(n))
                return [a, l];
              a += n[0] + n[1], n[0] = n[2], n[1] = n[3], n[2] = 0, n[3] = 0, o--;
            } else
              o++;
            n[o] = 1, i = !i;
          }
        throw new O();
      }
      parseFoundFinderPattern(e, t, n, r) {
        let i = e.get(r[0]), s = r[0] - 1;
        for (; s >= 0 && i !== e.get(s); )
          s--;
        s++;
        const o = r[0] - s, a = this.getDecodeFinderCounters(), l = new Int32Array(a.length);
        re.arraycopy(a, 0, l, 1, a.length - 1), l[0] = o;
        const c = this.parseFinderValue(l, _e.FINDER_PATTERNS);
        let f = s, d = r[1];
        return n && (f = e.getSize() - 1 - f, d = e.getSize() - 1 - d), new In(c, [s, r[1]], f, d, t);
      }
      adjustOddEvenCounts(e, t) {
        let n = oe.sum(new Int32Array(this.getOddCounts())), r = oe.sum(new Int32Array(this.getEvenCounts())), i = !1, s = !1, o = !1, a = !1;
        e ? (n > 12 ? s = !0 : n < 4 && (i = !0), r > 12 ? a = !0 : r < 4 && (o = !0)) : (n > 11 ? s = !0 : n < 5 && (i = !0), r > 10 ? a = !0 : r < 4 && (o = !0));
        let l = n + r - t, c = (n & 1) === (e ? 1 : 0), f = (r & 1) === 1;
        if (l === 1)
          if (c) {
            if (f)
              throw new O();
            s = !0;
          } else {
            if (!f)
              throw new O();
            a = !0;
          }
        else if (l === -1)
          if (c) {
            if (f)
              throw new O();
            i = !0;
          } else {
            if (!f)
              throw new O();
            o = !0;
          }
        else if (l === 0) {
          if (c) {
            if (!f)
              throw new O();
            n < r ? (i = !0, a = !0) : (s = !0, o = !0);
          } else if (f)
            throw new O();
        } else
          throw new O();
        if (i) {
          if (s)
            throw new O();
          ze.increment(this.getOddCounts(), this.getOddRoundingErrors());
        }
        if (s && ze.decrement(this.getOddCounts(), this.getOddRoundingErrors()), o) {
          if (a)
            throw new O();
          ze.increment(this.getEvenCounts(), this.getOddRoundingErrors());
        }
        a && ze.decrement(this.getEvenCounts(), this.getEvenRoundingErrors());
      }
    }
    _e.OUTSIDE_EVEN_TOTAL_SUBSET = [1, 10, 34, 70, 126], _e.INSIDE_ODD_TOTAL_SUBSET = [4, 20, 48, 81], _e.OUTSIDE_GSUM = [0, 161, 961, 2015, 2715], _e.INSIDE_GSUM = [0, 336, 1036, 1516], _e.OUTSIDE_ODD_WIDEST = [8, 6, 4, 3, 1], _e.INSIDE_ODD_WIDEST = [2, 4, 6, 8], _e.FINDER_PATTERNS = [
      Int32Array.from([3, 8, 2, 1]),
      Int32Array.from([3, 5, 5, 1]),
      Int32Array.from([3, 3, 7, 1]),
      Int32Array.from([3, 1, 9, 1]),
      Int32Array.from([2, 7, 4, 1]),
      Int32Array.from([2, 5, 6, 1]),
      Int32Array.from([2, 3, 8, 1]),
      Int32Array.from([1, 5, 7, 1]),
      Int32Array.from([1, 3, 9, 1])
    ];
    class Kt extends Me {
      constructor(e, t) {
        super(), this.readers = [], this.verbose = t === !0;
        const n = e ? e.get(xe.POSSIBLE_FORMATS) : null, r = e && e.get(xe.ASSUME_CODE_39_CHECK_DIGIT) !== void 0;
        n ? ((n.includes(z.EAN_13) || n.includes(z.UPC_A) || n.includes(z.EAN_8) || n.includes(z.UPC_E)) && this.readers.push(new kn(e)), n.includes(z.CODE_39) && this.readers.push(new ve(r)), n.includes(z.CODE_128) && this.readers.push(new V()), n.includes(z.ITF) && this.readers.push(new Ae()), n.includes(z.RSS_14) && this.readers.push(new _e()), n.includes(z.RSS_EXPANDED) && this.readers.push(new v(this.verbose))) : (this.readers.push(new kn(e)), this.readers.push(new ve()), this.readers.push(new kn(e)), this.readers.push(new V()), this.readers.push(new Ae()), this.readers.push(new _e()), this.readers.push(new v(this.verbose)));
      }
      // @Override
      decodeRow(e, t, n) {
        for (let r = 0; r < this.readers.length; r++)
          try {
            return this.readers[r].decodeRow(e, t, n);
          } catch {
          }
        throw new O();
      }
      // @Override
      reset() {
        this.readers.forEach((e) => e.reset());
      }
    }
    class Zr extends Lt {
      /**
       * Creates an instance of BrowserBarcodeReader.
       * @param {number} [timeBetweenScansMillis=500] the time delay between subsequent decode tries
       * @param {Map<DecodeHintType, any>} hints
       */
      constructor(e = 500, t) {
        super(new Kt(t), e, t);
      }
    }
    class fe {
      constructor(e, t, n) {
        this.ecCodewords = e, this.ecBlocks = [t], n && this.ecBlocks.push(n);
      }
      getECCodewords() {
        return this.ecCodewords;
      }
      getECBlocks() {
        return this.ecBlocks;
      }
    }
    class ue {
      constructor(e, t) {
        this.count = e, this.dataCodewords = t;
      }
      getCount() {
        return this.count;
      }
      getDataCodewords() {
        return this.dataCodewords;
      }
    }
    class se {
      constructor(e, t, n, r, i, s) {
        this.versionNumber = e, this.symbolSizeRows = t, this.symbolSizeColumns = n, this.dataRegionSizeRows = r, this.dataRegionSizeColumns = i, this.ecBlocks = s;
        let o = 0;
        const a = s.getECCodewords(), l = s.getECBlocks();
        for (let c of l)
          o += c.getCount() * (c.getDataCodewords() + a);
        this.totalCodewords = o;
      }
      getVersionNumber() {
        return this.versionNumber;
      }
      getSymbolSizeRows() {
        return this.symbolSizeRows;
      }
      getSymbolSizeColumns() {
        return this.symbolSizeColumns;
      }
      getDataRegionSizeRows() {
        return this.dataRegionSizeRows;
      }
      getDataRegionSizeColumns() {
        return this.dataRegionSizeColumns;
      }
      getTotalCodewords() {
        return this.totalCodewords;
      }
      getECBlocks() {
        return this.ecBlocks;
      }
      /**
       * <p>Deduces version information from Data Matrix dimensions.</p>
       *
       * @param numRows Number of rows in modules
       * @param numColumns Number of columns in modules
       * @return Version for a Data Matrix Code of those dimensions
       * @throws FormatException if dimensions do correspond to a valid Data Matrix size
       */
      static getVersionForDimensions(e, t) {
        if (e & 1 || t & 1)
          throw new U();
        for (let n of se.VERSIONS)
          if (n.symbolSizeRows === e && n.symbolSizeColumns === t)
            return n;
        throw new U();
      }
      //  @Override
      toString() {
        return "" + this.versionNumber;
      }
      /**
       * See ISO 16022:2006 5.5.1 Table 7
       */
      static buildVersions() {
        return [
          new se(1, 10, 10, 8, 8, new fe(5, new ue(1, 3))),
          new se(2, 12, 12, 10, 10, new fe(7, new ue(1, 5))),
          new se(3, 14, 14, 12, 12, new fe(10, new ue(1, 8))),
          new se(4, 16, 16, 14, 14, new fe(12, new ue(1, 12))),
          new se(5, 18, 18, 16, 16, new fe(14, new ue(1, 18))),
          new se(6, 20, 20, 18, 18, new fe(18, new ue(1, 22))),
          new se(7, 22, 22, 20, 20, new fe(20, new ue(1, 30))),
          new se(8, 24, 24, 22, 22, new fe(24, new ue(1, 36))),
          new se(9, 26, 26, 24, 24, new fe(28, new ue(1, 44))),
          new se(10, 32, 32, 14, 14, new fe(36, new ue(1, 62))),
          new se(11, 36, 36, 16, 16, new fe(42, new ue(1, 86))),
          new se(12, 40, 40, 18, 18, new fe(48, new ue(1, 114))),
          new se(13, 44, 44, 20, 20, new fe(56, new ue(1, 144))),
          new se(14, 48, 48, 22, 22, new fe(68, new ue(1, 174))),
          new se(15, 52, 52, 24, 24, new fe(42, new ue(2, 102))),
          new se(16, 64, 64, 14, 14, new fe(56, new ue(2, 140))),
          new se(17, 72, 72, 16, 16, new fe(36, new ue(4, 92))),
          new se(18, 80, 80, 18, 18, new fe(48, new ue(4, 114))),
          new se(19, 88, 88, 20, 20, new fe(56, new ue(4, 144))),
          new se(20, 96, 96, 22, 22, new fe(68, new ue(4, 174))),
          new se(21, 104, 104, 24, 24, new fe(56, new ue(6, 136))),
          new se(22, 120, 120, 18, 18, new fe(68, new ue(6, 175))),
          new se(23, 132, 132, 20, 20, new fe(62, new ue(8, 163))),
          new se(24, 144, 144, 22, 22, new fe(62, new ue(8, 156), new ue(2, 155))),
          new se(25, 8, 18, 6, 16, new fe(7, new ue(1, 5))),
          new se(26, 8, 32, 6, 14, new fe(11, new ue(1, 10))),
          new se(27, 12, 26, 10, 24, new fe(14, new ue(1, 16))),
          new se(28, 12, 36, 10, 16, new fe(18, new ue(1, 22))),
          new se(29, 16, 36, 14, 16, new fe(24, new ue(1, 32))),
          new se(30, 16, 48, 14, 22, new fe(28, new ue(1, 49)))
        ];
      }
    }
    se.VERSIONS = se.buildVersions();
    class Hn {
      /**
       * @param bitMatrix {@link BitMatrix} to parse
       * @throws FormatException if dimension is < 8 or > 144 or not 0 mod 2
       */
      constructor(e) {
        const t = e.getHeight();
        if (t < 8 || t > 144 || t & 1)
          throw new U();
        this.version = Hn.readVersion(e), this.mappingBitMatrix = this.extractDataRegion(e), this.readMappingMatrix = new Fe(this.mappingBitMatrix.getWidth(), this.mappingBitMatrix.getHeight());
      }
      getVersion() {
        return this.version;
      }
      /**
       * <p>Creates the version object based on the dimension of the original bit matrix from
       * the datamatrix code.</p>
       *
       * <p>See ISO 16022:2006 Table 7 - ECC 200 symbol attributes</p>
       *
       * @param bitMatrix Original {@link BitMatrix} including alignment patterns
       * @return {@link Version} encapsulating the Data Matrix Code's "version"
       * @throws FormatException if the dimensions of the mapping matrix are not valid
       * Data Matrix dimensions.
       */
      static readVersion(e) {
        const t = e.getHeight(), n = e.getWidth();
        return se.getVersionForDimensions(t, n);
      }
      /**
       * <p>Reads the bits in the {@link BitMatrix} representing the mapping matrix (No alignment patterns)
       * in the correct order in order to reconstitute the codewords bytes contained within the
       * Data Matrix Code.</p>
       *
       * @return bytes encoded within the Data Matrix Code
       * @throws FormatException if the exact number of bytes expected is not read
       */
      readCodewords() {
        const e = new Int8Array(this.version.getTotalCodewords());
        let t = 0, n = 4, r = 0;
        const i = this.mappingBitMatrix.getHeight(), s = this.mappingBitMatrix.getWidth();
        let o = !1, a = !1, l = !1, c = !1;
        do
          if (n === i && r === 0 && !o)
            e[t++] = this.readCorner1(i, s) & 255, n -= 2, r += 2, o = !0;
          else if (n === i - 2 && r === 0 && s & 3 && !a)
            e[t++] = this.readCorner2(i, s) & 255, n -= 2, r += 2, a = !0;
          else if (n === i + 4 && r === 2 && !(s & 7) && !l)
            e[t++] = this.readCorner3(i, s) & 255, n -= 2, r += 2, l = !0;
          else if (n === i - 2 && r === 0 && (s & 7) === 4 && !c)
            e[t++] = this.readCorner4(i, s) & 255, n -= 2, r += 2, c = !0;
          else {
            do
              n < i && r >= 0 && !this.readMappingMatrix.get(r, n) && (e[t++] = this.readUtah(n, r, i, s) & 255), n -= 2, r += 2;
            while (n >= 0 && r < s);
            n += 1, r += 3;
            do
              n >= 0 && r < s && !this.readMappingMatrix.get(r, n) && (e[t++] = this.readUtah(n, r, i, s) & 255), n += 2, r -= 2;
            while (n < i && r >= 0);
            n += 3, r += 1;
          }
        while (n < i || r < s);
        if (t !== this.version.getTotalCodewords())
          throw new U();
        return e;
      }
      /**
       * <p>Reads a bit of the mapping matrix accounting for boundary wrapping.</p>
       *
       * @param row Row to read in the mapping matrix
       * @param column Column to read in the mapping matrix
       * @param numRows Number of rows in the mapping matrix
       * @param numColumns Number of columns in the mapping matrix
       * @return value of the given bit in the mapping matrix
       */
      readModule(e, t, n, r) {
        return e < 0 && (e += n, t += 4 - (n + 4 & 7)), t < 0 && (t += r, e += 4 - (r + 4 & 7)), this.readMappingMatrix.set(t, e), this.mappingBitMatrix.get(t, e);
      }
      /**
       * <p>Reads the 8 bits of the standard Utah-shaped pattern.</p>
       *
       * <p>See ISO 16022:2006, 5.8.1 Figure 6</p>
       *
       * @param row Current row in the mapping matrix, anchored at the 8th bit (LSB) of the pattern
       * @param column Current column in the mapping matrix, anchored at the 8th bit (LSB) of the pattern
       * @param numRows Number of rows in the mapping matrix
       * @param numColumns Number of columns in the mapping matrix
       * @return byte from the utah shape
       */
      readUtah(e, t, n, r) {
        let i = 0;
        return this.readModule(e - 2, t - 2, n, r) && (i |= 1), i <<= 1, this.readModule(e - 2, t - 1, n, r) && (i |= 1), i <<= 1, this.readModule(e - 1, t - 2, n, r) && (i |= 1), i <<= 1, this.readModule(e - 1, t - 1, n, r) && (i |= 1), i <<= 1, this.readModule(e - 1, t, n, r) && (i |= 1), i <<= 1, this.readModule(e, t - 2, n, r) && (i |= 1), i <<= 1, this.readModule(e, t - 1, n, r) && (i |= 1), i <<= 1, this.readModule(e, t, n, r) && (i |= 1), i;
      }
      /**
       * <p>Reads the 8 bits of the special corner condition 1.</p>
       *
       * <p>See ISO 16022:2006, Figure F.3</p>
       *
       * @param numRows Number of rows in the mapping matrix
       * @param numColumns Number of columns in the mapping matrix
       * @return byte from the Corner condition 1
       */
      readCorner1(e, t) {
        let n = 0;
        return this.readModule(e - 1, 0, e, t) && (n |= 1), n <<= 1, this.readModule(e - 1, 1, e, t) && (n |= 1), n <<= 1, this.readModule(e - 1, 2, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 2, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(1, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(2, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(3, t - 1, e, t) && (n |= 1), n;
      }
      /**
       * <p>Reads the 8 bits of the special corner condition 2.</p>
       *
       * <p>See ISO 16022:2006, Figure F.4</p>
       *
       * @param numRows Number of rows in the mapping matrix
       * @param numColumns Number of columns in the mapping matrix
       * @return byte from the Corner condition 2
       */
      readCorner2(e, t) {
        let n = 0;
        return this.readModule(e - 3, 0, e, t) && (n |= 1), n <<= 1, this.readModule(e - 2, 0, e, t) && (n |= 1), n <<= 1, this.readModule(e - 1, 0, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 4, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 3, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 2, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(1, t - 1, e, t) && (n |= 1), n;
      }
      /**
       * <p>Reads the 8 bits of the special corner condition 3.</p>
       *
       * <p>See ISO 16022:2006, Figure F.5</p>
       *
       * @param numRows Number of rows in the mapping matrix
       * @param numColumns Number of columns in the mapping matrix
       * @return byte from the Corner condition 3
       */
      readCorner3(e, t) {
        let n = 0;
        return this.readModule(e - 1, 0, e, t) && (n |= 1), n <<= 1, this.readModule(e - 1, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 3, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 2, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(1, t - 3, e, t) && (n |= 1), n <<= 1, this.readModule(1, t - 2, e, t) && (n |= 1), n <<= 1, this.readModule(1, t - 1, e, t) && (n |= 1), n;
      }
      /**
       * <p>Reads the 8 bits of the special corner condition 4.</p>
       *
       * <p>See ISO 16022:2006, Figure F.6</p>
       *
       * @param numRows Number of rows in the mapping matrix
       * @param numColumns Number of columns in the mapping matrix
       * @return byte from the Corner condition 4
       */
      readCorner4(e, t) {
        let n = 0;
        return this.readModule(e - 3, 0, e, t) && (n |= 1), n <<= 1, this.readModule(e - 2, 0, e, t) && (n |= 1), n <<= 1, this.readModule(e - 1, 0, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 2, e, t) && (n |= 1), n <<= 1, this.readModule(0, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(1, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(2, t - 1, e, t) && (n |= 1), n <<= 1, this.readModule(3, t - 1, e, t) && (n |= 1), n;
      }
      /**
       * <p>Extracts the data region from a {@link BitMatrix} that contains
       * alignment patterns.</p>
       *
       * @param bitMatrix Original {@link BitMatrix} with alignment patterns
       * @return BitMatrix that has the alignment patterns removed
       */
      extractDataRegion(e) {
        const t = this.version.getSymbolSizeRows(), n = this.version.getSymbolSizeColumns();
        if (e.getHeight() !== t)
          throw new D("Dimension of bitMatrix must match the version size");
        const r = this.version.getDataRegionSizeRows(), i = this.version.getDataRegionSizeColumns(), s = t / r | 0, o = n / i | 0, a = s * r, l = o * i, c = new Fe(l, a);
        for (let f = 0; f < s; ++f) {
          const d = f * r;
          for (let C = 0; C < o; ++C) {
            const p = C * i;
            for (let b = 0; b < r; ++b) {
              const y = f * (r + 2) + 1 + b, _ = d + b;
              for (let P = 0; P < i; ++P) {
                const F = C * (i + 2) + 1 + P;
                if (e.get(F, y)) {
                  const L = p + P;
                  c.set(L, _);
                }
              }
            }
          }
        }
        return c;
      }
    }
    class Wn {
      constructor(e, t) {
        this.numDataCodewords = e, this.codewords = t;
      }
      /**
       * <p>When Data Matrix Codes use multiple data blocks, they actually interleave the bytes of each of them.
       * That is, the first byte of data block 1 to n is written, then the second bytes, and so on. This
       * method will separate the data into original blocks.</p>
       *
       * @param rawCodewords bytes as read directly from the Data Matrix Code
       * @param version version of the Data Matrix Code
       * @return DataBlocks containing original bytes, "de-interleaved" from representation in the
       *         Data Matrix Code
       */
      static getDataBlocks(e, t) {
        const n = t.getECBlocks();
        let r = 0;
        const i = n.getECBlocks();
        for (let b of i)
          r += b.getCount();
        const s = new Array(r);
        let o = 0;
        for (let b of i)
          for (let y = 0; y < b.getCount(); y++) {
            const _ = b.getDataCodewords(), P = n.getECCodewords() + _;
            s[o++] = new Wn(_, new Uint8Array(P));
          }
        const l = s[0].codewords.length - n.getECCodewords(), c = l - 1;
        let f = 0;
        for (let b = 0; b < c; b++)
          for (let y = 0; y < o; y++)
            s[y].codewords[b] = e[f++];
        const d = t.getVersionNumber() === 24, C = d ? 8 : o;
        for (let b = 0; b < C; b++)
          s[b].codewords[l - 1] = e[f++];
        const p = s[0].codewords.length;
        for (let b = l; b < p; b++)
          for (let y = 0; y < o; y++) {
            const _ = d ? (y + 8) % o : y, P = d && _ > 7 ? b - 1 : b;
            s[_].codewords[P] = e[f++];
          }
        if (f !== e.length)
          throw new D();
        return s;
      }
      getNumDataCodewords() {
        return this.numDataCodewords;
      }
      getCodewords() {
        return this.codewords;
      }
    }
    class Xn {
      /**
       * @param bytes bytes from which this will read bits. Bits will be read from the first byte first.
       * Bits are read within a byte from most-significant to least-significant bit.
       */
      constructor(e) {
        this.bytes = e, this.byteOffset = 0, this.bitOffset = 0;
      }
      /**
       * @return index of next bit in current byte which would be read by the next call to {@link #readBits(int)}.
       */
      getBitOffset() {
        return this.bitOffset;
      }
      /**
       * @return index of next byte in input byte array which would be read by the next call to {@link #readBits(int)}.
       */
      getByteOffset() {
        return this.byteOffset;
      }
      /**
       * @param numBits number of bits to read
       * @return int representing the bits read. The bits will appear as the least-significant
       *         bits of the int
       * @throws IllegalArgumentException if numBits isn't in [1,32] or more than is available
       */
      readBits(e) {
        if (e < 1 || e > 32 || e > this.available())
          throw new D("" + e);
        let t = 0, n = this.bitOffset, r = this.byteOffset;
        const i = this.bytes;
        if (n > 0) {
          const s = 8 - n, o = e < s ? e : s, a = s - o, l = 255 >> 8 - o << a;
          t = (i[r] & l) >> a, e -= o, n += o, n === 8 && (n = 0, r++);
        }
        if (e > 0) {
          for (; e >= 8; )
            t = t << 8 | i[r] & 255, r++, e -= 8;
          if (e > 0) {
            const s = 8 - e, o = 255 >> s << s;
            t = t << e | (i[r] & o) >> s, n += e;
          }
        }
        return this.bitOffset = n, this.byteOffset = r, t;
      }
      /**
       * @return number of bits that can be read successfully
       */
      available() {
        return 8 * (this.bytes.length - this.byteOffset) - this.bitOffset;
      }
    }
    var Le;
    (function(u) {
      u[u.PAD_ENCODE = 0] = "PAD_ENCODE", u[u.ASCII_ENCODE = 1] = "ASCII_ENCODE", u[u.C40_ENCODE = 2] = "C40_ENCODE", u[u.TEXT_ENCODE = 3] = "TEXT_ENCODE", u[u.ANSIX12_ENCODE = 4] = "ANSIX12_ENCODE", u[u.EDIFACT_ENCODE = 5] = "EDIFACT_ENCODE", u[u.BASE256_ENCODE = 6] = "BASE256_ENCODE";
    })(Le || (Le = {}));
    class Rt {
      static decode(e) {
        const t = new Xn(e), n = new ge(), r = new ge(), i = new Array();
        let s = Le.ASCII_ENCODE;
        do
          if (s === Le.ASCII_ENCODE)
            s = this.decodeAsciiSegment(t, n, r);
          else {
            switch (s) {
              case Le.C40_ENCODE:
                this.decodeC40Segment(t, n);
                break;
              case Le.TEXT_ENCODE:
                this.decodeTextSegment(t, n);
                break;
              case Le.ANSIX12_ENCODE:
                this.decodeAnsiX12Segment(t, n);
                break;
              case Le.EDIFACT_ENCODE:
                this.decodeEdifactSegment(t, n);
                break;
              case Le.BASE256_ENCODE:
                this.decodeBase256Segment(t, n, i);
                break;
              default:
                throw new U();
            }
            s = Le.ASCII_ENCODE;
          }
        while (s !== Le.PAD_ENCODE && t.available() > 0);
        return r.length() > 0 && n.append(r.toString()), new nn(e, n.toString(), i.length === 0 ? null : i, null);
      }
      /**
       * See ISO 16022:2006, 5.2.3 and Annex C, Table C.2
       */
      static decodeAsciiSegment(e, t, n) {
        let r = !1;
        do {
          let i = e.readBits(8);
          if (i === 0)
            throw new U();
          if (i <= 128)
            return r && (i += 128), t.append(String.fromCharCode(i - 1)), Le.ASCII_ENCODE;
          if (i === 129)
            return Le.PAD_ENCODE;
          if (i <= 229) {
            const s = i - 130;
            s < 10 && t.append("0"), t.append("" + s);
          } else
            switch (i) {
              case 230:
                return Le.C40_ENCODE;
              case 231:
                return Le.BASE256_ENCODE;
              case 232:
                t.append("");
                break;
              case 233:
              case 234:
                break;
              case 235:
                r = !0;
                break;
              case 236:
                t.append("[)>05"), n.insert(0, "");
                break;
              case 237:
                t.append("[)>06"), n.insert(0, "");
                break;
              case 238:
                return Le.ANSIX12_ENCODE;
              case 239:
                return Le.TEXT_ENCODE;
              case 240:
                return Le.EDIFACT_ENCODE;
              case 241:
                break;
              default:
                if (i !== 254 || e.available() !== 0)
                  throw new U();
                break;
            }
        } while (e.available() > 0);
        return Le.ASCII_ENCODE;
      }
      /**
       * See ISO 16022:2006, 5.2.5 and Annex C, Table C.1
       */
      static decodeC40Segment(e, t) {
        let n = !1;
        const r = [];
        let i = 0;
        do {
          if (e.available() === 8)
            return;
          const s = e.readBits(8);
          if (s === 254)
            return;
          this.parseTwoBytes(s, e.readBits(8), r);
          for (let o = 0; o < 3; o++) {
            const a = r[o];
            switch (i) {
              case 0:
                if (a < 3)
                  i = a + 1;
                else if (a < this.C40_BASIC_SET_CHARS.length) {
                  const l = this.C40_BASIC_SET_CHARS[a];
                  n ? (t.append(String.fromCharCode(l.charCodeAt(0) + 128)), n = !1) : t.append(l);
                } else
                  throw new U();
                break;
              case 1:
                n ? (t.append(String.fromCharCode(a + 128)), n = !1) : t.append(String.fromCharCode(a)), i = 0;
                break;
              case 2:
                if (a < this.C40_SHIFT2_SET_CHARS.length) {
                  const l = this.C40_SHIFT2_SET_CHARS[a];
                  n ? (t.append(String.fromCharCode(l.charCodeAt(0) + 128)), n = !1) : t.append(l);
                } else
                  switch (a) {
                    case 27:
                      t.append("");
                      break;
                    case 30:
                      n = !0;
                      break;
                    default:
                      throw new U();
                  }
                i = 0;
                break;
              case 3:
                n ? (t.append(String.fromCharCode(a + 224)), n = !1) : t.append(String.fromCharCode(a + 96)), i = 0;
                break;
              default:
                throw new U();
            }
          }
        } while (e.available() > 0);
      }
      /**
       * See ISO 16022:2006, 5.2.6 and Annex C, Table C.2
       */
      static decodeTextSegment(e, t) {
        let n = !1, r = [], i = 0;
        do {
          if (e.available() === 8)
            return;
          const s = e.readBits(8);
          if (s === 254)
            return;
          this.parseTwoBytes(s, e.readBits(8), r);
          for (let o = 0; o < 3; o++) {
            const a = r[o];
            switch (i) {
              case 0:
                if (a < 3)
                  i = a + 1;
                else if (a < this.TEXT_BASIC_SET_CHARS.length) {
                  const l = this.TEXT_BASIC_SET_CHARS[a];
                  n ? (t.append(String.fromCharCode(l.charCodeAt(0) + 128)), n = !1) : t.append(l);
                } else
                  throw new U();
                break;
              case 1:
                n ? (t.append(String.fromCharCode(a + 128)), n = !1) : t.append(String.fromCharCode(a)), i = 0;
                break;
              case 2:
                if (a < this.TEXT_SHIFT2_SET_CHARS.length) {
                  const l = this.TEXT_SHIFT2_SET_CHARS[a];
                  n ? (t.append(String.fromCharCode(l.charCodeAt(0) + 128)), n = !1) : t.append(l);
                } else
                  switch (a) {
                    case 27:
                      t.append("");
                      break;
                    case 30:
                      n = !0;
                      break;
                    default:
                      throw new U();
                  }
                i = 0;
                break;
              case 3:
                if (a < this.TEXT_SHIFT3_SET_CHARS.length) {
                  const l = this.TEXT_SHIFT3_SET_CHARS[a];
                  n ? (t.append(String.fromCharCode(l.charCodeAt(0) + 128)), n = !1) : t.append(l), i = 0;
                } else
                  throw new U();
                break;
              default:
                throw new U();
            }
          }
        } while (e.available() > 0);
      }
      /**
       * See ISO 16022:2006, 5.2.7
       */
      static decodeAnsiX12Segment(e, t) {
        const n = [];
        do {
          if (e.available() === 8)
            return;
          const r = e.readBits(8);
          if (r === 254)
            return;
          this.parseTwoBytes(r, e.readBits(8), n);
          for (let i = 0; i < 3; i++) {
            const s = n[i];
            switch (s) {
              case 0:
                t.append("\r");
                break;
              case 1:
                t.append("*");
                break;
              case 2:
                t.append(">");
                break;
              case 3:
                t.append(" ");
                break;
              default:
                if (s < 14)
                  t.append(String.fromCharCode(s + 44));
                else if (s < 40)
                  t.append(String.fromCharCode(s + 51));
                else
                  throw new U();
                break;
            }
          }
        } while (e.available() > 0);
      }
      static parseTwoBytes(e, t, n) {
        let r = (e << 8) + t - 1, i = Math.floor(r / 1600);
        n[0] = i, r -= i * 1600, i = Math.floor(r / 40), n[1] = i, n[2] = r - i * 40;
      }
      /**
       * See ISO 16022:2006, 5.2.8 and Annex C Table C.3
       */
      static decodeEdifactSegment(e, t) {
        do {
          if (e.available() <= 16)
            return;
          for (let n = 0; n < 4; n++) {
            let r = e.readBits(6);
            if (r === 31) {
              const i = 8 - e.getBitOffset();
              i !== 8 && e.readBits(i);
              return;
            }
            r & 32 || (r |= 64), t.append(String.fromCharCode(r));
          }
        } while (e.available() > 0);
      }
      /**
       * See ISO 16022:2006, 5.2.9 and Annex B, B.2
       */
      static decodeBase256Segment(e, t, n) {
        let r = 1 + e.getByteOffset();
        const i = this.unrandomize255State(e.readBits(8), r++);
        let s;
        if (i === 0 ? s = e.available() / 8 | 0 : i < 250 ? s = i : s = 250 * (i - 249) + this.unrandomize255State(e.readBits(8), r++), s < 0)
          throw new U();
        const o = new Uint8Array(s);
        for (let a = 0; a < s; a++) {
          if (e.available() < 8)
            throw new U();
          o[a] = this.unrandomize255State(e.readBits(8), r++);
        }
        n.push(o);
        try {
          t.append(Je.decode(o, q.ISO88591));
        } catch (a) {
          throw new gt("Platform does not support required encoding: " + a.message);
        }
      }
      /**
       * See ISO 16022:2006, Annex B, B.2
       */
      static unrandomize255State(e, t) {
        const n = 149 * t % 255 + 1, r = e - n;
        return r >= 0 ? r : r + 256;
      }
    }
    Rt.C40_BASIC_SET_CHARS = [
      "*",
      "*",
      "*",
      " ",
      "0",
      "1",
      "2",
      "3",
      "4",
      "5",
      "6",
      "7",
      "8",
      "9",
      "A",
      "B",
      "C",
      "D",
      "E",
      "F",
      "G",
      "H",
      "I",
      "J",
      "K",
      "L",
      "M",
      "N",
      "O",
      "P",
      "Q",
      "R",
      "S",
      "T",
      "U",
      "V",
      "W",
      "X",
      "Y",
      "Z"
    ], Rt.C40_SHIFT2_SET_CHARS = [
      "!",
      '"',
      "#",
      "$",
      "%",
      "&",
      "'",
      "(",
      ")",
      "*",
      "+",
      ",",
      "-",
      ".",
      "/",
      ":",
      ";",
      "<",
      "=",
      ">",
      "?",
      "@",
      "[",
      "\\",
      "]",
      "^",
      "_"
    ], Rt.TEXT_BASIC_SET_CHARS = [
      "*",
      "*",
      "*",
      " ",
      "0",
      "1",
      "2",
      "3",
      "4",
      "5",
      "6",
      "7",
      "8",
      "9",
      "a",
      "b",
      "c",
      "d",
      "e",
      "f",
      "g",
      "h",
      "i",
      "j",
      "k",
      "l",
      "m",
      "n",
      "o",
      "p",
      "q",
      "r",
      "s",
      "t",
      "u",
      "v",
      "w",
      "x",
      "y",
      "z"
    ], Rt.TEXT_SHIFT2_SET_CHARS = Rt.C40_SHIFT2_SET_CHARS, Rt.TEXT_SHIFT3_SET_CHARS = [
      "`",
      "A",
      "B",
      "C",
      "D",
      "E",
      "F",
      "G",
      "H",
      "I",
      "J",
      "K",
      "L",
      "M",
      "N",
      "O",
      "P",
      "Q",
      "R",
      "S",
      "T",
      "U",
      "V",
      "W",
      "X",
      "Y",
      "Z",
      "{",
      "|",
      "}",
      "~",
      ""
    ];
    class Kr {
      constructor() {
        this.rsDecoder = new sn(ce.DATA_MATRIX_FIELD_256);
      }
      /**
       * <p>Decodes a Data Matrix Code represented as a {@link BitMatrix}. A 1 or "true" is taken
       * to mean a black module.</p>
       *
       * @param bits booleans representing white/black Data Matrix Code modules
       * @return text and bytes encoded within the Data Matrix Code
       * @throws FormatException if the Data Matrix Code cannot be decoded
       * @throws ChecksumException if error correction fails
       */
      decode(e) {
        const t = new Hn(e), n = t.getVersion(), r = t.readCodewords(), i = Wn.getDataBlocks(r, n);
        let s = 0;
        for (let l of i)
          s += l.getNumDataCodewords();
        const o = new Uint8Array(s), a = i.length;
        for (let l = 0; l < a; l++) {
          const c = i[l], f = c.getCodewords(), d = c.getNumDataCodewords();
          this.correctErrors(f, d);
          for (let C = 0; C < d; C++)
            o[C * a + l] = f[C];
        }
        return Rt.decode(o);
      }
      /**
       * <p>Given data and error-correction codewords received, possibly corrupted by errors, attempts to
       * correct the errors in-place using Reed-Solomon error correction.</p>
       *
       * @param codewordBytes data and error correction codewords
       * @param numDataCodewords number of codewords that are data bytes
       * @throws ChecksumException if error correction fails
       */
      correctErrors(e, t) {
        const n = new Int32Array(e);
        try {
          this.rsDecoder.decode(n, e.length - t);
        } catch {
          throw new J();
        }
        for (let r = 0; r < t; r++)
          e[r] = n[r];
      }
    }
    class De {
      constructor(e) {
        this.image = e, this.rectangleDetector = new pt(this.image);
      }
      /**
       * <p>Detects a Data Matrix Code in an image.</p>
       *
       * @return {@link DetectorResult} encapsulating results of detecting a Data Matrix Code
       * @throws NotFoundException if no Data Matrix Code can be found
       */
      detect() {
        const e = this.rectangleDetector.detect();
        let t = this.detectSolid1(e);
        if (t = this.detectSolid2(t), t[3] = this.correctTopRight(t), !t[3])
          throw new O();
        t = this.shiftToModuleCenter(t);
        const n = t[0], r = t[1], i = t[2], s = t[3];
        let o = this.transitionsBetween(n, s) + 1, a = this.transitionsBetween(i, s) + 1;
        (o & 1) === 1 && (o += 1), (a & 1) === 1 && (a += 1), 4 * o < 7 * a && 4 * a < 7 * o && (o = a = Math.max(o, a));
        let l = De.sampleGrid(this.image, n, r, i, s, o, a);
        return new Cn(l, [n, r, i, s]);
      }
      static shiftPoint(e, t, n) {
        let r = (t.getX() - e.getX()) / (n + 1), i = (t.getY() - e.getY()) / (n + 1);
        return new W(e.getX() + r, e.getY() + i);
      }
      static moveAway(e, t, n) {
        let r = e.getX(), i = e.getY();
        return r < t ? r -= 1 : r += 1, i < n ? i -= 1 : i += 1, new W(r, i);
      }
      /**
       * Detect a solid side which has minimum transition.
       */
      detectSolid1(e) {
        let t = e[0], n = e[1], r = e[3], i = e[2], s = this.transitionsBetween(t, n), o = this.transitionsBetween(n, r), a = this.transitionsBetween(r, i), l = this.transitionsBetween(i, t), c = s, f = [i, t, n, r];
        return c > o && (c = o, f[0] = t, f[1] = n, f[2] = r, f[3] = i), c > a && (c = a, f[0] = n, f[1] = r, f[2] = i, f[3] = t), c > l && (f[0] = r, f[1] = i, f[2] = t, f[3] = n), f;
      }
      /**
       * Detect a second solid side next to first solid side.
       */
      detectSolid2(e) {
        let t = e[0], n = e[1], r = e[2], i = e[3], s = this.transitionsBetween(t, i), o = De.shiftPoint(n, r, (s + 1) * 4), a = De.shiftPoint(r, n, (s + 1) * 4), l = this.transitionsBetween(o, t), c = this.transitionsBetween(a, i);
        return l < c ? (e[0] = t, e[1] = n, e[2] = r, e[3] = i) : (e[0] = n, e[1] = r, e[2] = i, e[3] = t), e;
      }
      /**
       * Calculates the corner position of the white top right module.
       */
      correctTopRight(e) {
        let t = e[0], n = e[1], r = e[2], i = e[3], s = this.transitionsBetween(t, i), o = this.transitionsBetween(n, i), a = De.shiftPoint(t, n, (o + 1) * 4), l = De.shiftPoint(r, n, (s + 1) * 4);
        s = this.transitionsBetween(a, i), o = this.transitionsBetween(l, i);
        let c = new W(i.getX() + (r.getX() - n.getX()) / (s + 1), i.getY() + (r.getY() - n.getY()) / (s + 1)), f = new W(i.getX() + (t.getX() - n.getX()) / (o + 1), i.getY() + (t.getY() - n.getY()) / (o + 1));
        if (!this.isValid(c))
          return this.isValid(f) ? f : null;
        if (!this.isValid(f))
          return c;
        let d = this.transitionsBetween(a, c) + this.transitionsBetween(l, c), C = this.transitionsBetween(a, f) + this.transitionsBetween(l, f);
        return d > C ? c : f;
      }
      /**
       * Shift the edge points to the module center.
       */
      shiftToModuleCenter(e) {
        let t = e[0], n = e[1], r = e[2], i = e[3], s = this.transitionsBetween(t, i) + 1, o = this.transitionsBetween(r, i) + 1, a = De.shiftPoint(t, n, o * 4), l = De.shiftPoint(r, n, s * 4);
        s = this.transitionsBetween(a, i) + 1, o = this.transitionsBetween(l, i) + 1, (s & 1) === 1 && (s += 1), (o & 1) === 1 && (o += 1);
        let c = (t.getX() + n.getX() + r.getX() + i.getX()) / 4, f = (t.getY() + n.getY() + r.getY() + i.getY()) / 4;
        t = De.moveAway(t, c, f), n = De.moveAway(n, c, f), r = De.moveAway(r, c, f), i = De.moveAway(i, c, f);
        let d, C;
        return a = De.shiftPoint(t, n, o * 4), a = De.shiftPoint(a, i, s * 4), d = De.shiftPoint(n, t, o * 4), d = De.shiftPoint(d, r, s * 4), l = De.shiftPoint(r, i, o * 4), l = De.shiftPoint(l, n, s * 4), C = De.shiftPoint(i, r, o * 4), C = De.shiftPoint(C, t, s * 4), [a, d, l, C];
      }
      isValid(e) {
        return e.getX() >= 0 && e.getX() < this.image.getWidth() && e.getY() > 0 && e.getY() < this.image.getHeight();
      }
      static sampleGrid(e, t, n, r, i, s, o) {
        return _t.getInstance().sampleGrid(e, s, o, 0.5, 0.5, s - 0.5, 0.5, s - 0.5, o - 0.5, 0.5, o - 0.5, t.getX(), t.getY(), i.getX(), i.getY(), r.getX(), r.getY(), n.getX(), n.getY());
      }
      /**
       * Counts the number of black/white transitions between two points, using something like Bresenham's algorithm.
       */
      transitionsBetween(e, t) {
        let n = Math.trunc(e.getX()), r = Math.trunc(e.getY()), i = Math.trunc(t.getX()), s = Math.trunc(t.getY()), o = Math.abs(s - r) > Math.abs(i - n);
        if (o) {
          let b = n;
          n = r, r = b, b = i, i = s, s = b;
        }
        let a = Math.abs(i - n), l = Math.abs(s - r), c = -a / 2, f = r < s ? 1 : -1, d = n < i ? 1 : -1, C = 0, p = this.image.get(o ? r : n, o ? n : r);
        for (let b = n, y = r; b !== i; b += d) {
          let _ = this.image.get(o ? y : b, o ? b : y);
          if (_ !== p && (C++, p = _), c += l, c > 0) {
            if (y === s)
              break;
            y += f, c -= a;
          }
        }
        return C;
      }
    }
    class Dt {
      constructor() {
        this.decoder = new Kr();
      }
      /**
       * Locates and decodes a Data Matrix code in an image.
       *
       * @return a String representing the content encoded by the Data Matrix code
       * @throws NotFoundException if a Data Matrix code cannot be found
       * @throws FormatException if a Data Matrix code cannot be decoded
       * @throws ChecksumException if error correction fails
       */
      // @Override
      // public Result decode(BinaryBitmap image) throws NotFoundException, ChecksumException, FormatException {
      //   return decode(image, null);
      // }
      // @Override
      decode(e, t = null) {
        let n, r;
        if (t != null && t.has(xe.PURE_BARCODE)) {
          const l = Dt.extractPureBits(e.getBlackMatrix());
          n = this.decoder.decode(l), r = Dt.NO_POINTS;
        } else {
          const l = new De(e.getBlackMatrix()).detect();
          n = this.decoder.decode(l.getBits()), r = l.getPoints();
        }
        const i = n.getRawBytes(), s = new je(n.getText(), i, 8 * i.length, r, z.DATA_MATRIX, re.currentTimeMillis()), o = n.getByteSegments();
        o != null && s.putMetadata(Ue.BYTE_SEGMENTS, o);
        const a = n.getECLevel();
        return a != null && s.putMetadata(Ue.ERROR_CORRECTION_LEVEL, a), s;
      }
      // @Override
      reset() {
      }
      /**
       * This method detects a code in a "pure" image -- that is, pure monochrome image
       * which contains only an unrotated, unskewed, image of a code, with some white border
       * around it. This is a specialized method that works exceptionally fast in this special
       * case.
       *
       * @see com.google.zxing.qrcode.QRCodeReader#extractPureBits(BitMatrix)
       */
      static extractPureBits(e) {
        const t = e.getTopLeftOnBit(), n = e.getBottomRightOnBit();
        if (t == null || n == null)
          throw new O();
        const r = this.moduleSize(t, e);
        let i = t[1];
        const s = n[1];
        let o = t[0];
        const l = (n[0] - o + 1) / r, c = (s - i + 1) / r;
        if (l <= 0 || c <= 0)
          throw new O();
        const f = r / 2;
        i += f, o += f;
        const d = new Fe(l, c);
        for (let C = 0; C < c; C++) {
          const p = i + C * r;
          for (let b = 0; b < l; b++)
            e.get(o + b * r, p) && d.set(b, C);
        }
        return d;
      }
      static moduleSize(e, t) {
        const n = t.getWidth();
        let r = e[0];
        const i = e[1];
        for (; r < n && t.get(r, i); )
          r++;
        if (r === n)
          throw new O();
        const s = r - e[0];
        if (s === 0)
          throw new O();
        return s;
      }
    }
    Dt.NO_POINTS = [];
    class jr extends Lt {
      /**
       * Creates an instance of BrowserQRCodeReader.
       * @param {number} [timeBetweenScansMillis=500] the time delay between subsequent decode tries
       */
      constructor(e = 500) {
        super(new Dt(), e);
      }
    }
    var jt;
    (function(u) {
      u[u.L = 0] = "L", u[u.M = 1] = "M", u[u.Q = 2] = "Q", u[u.H = 3] = "H";
    })(jt || (jt = {}));
    class pe {
      constructor(e, t, n) {
        this.value = e, this.stringValue = t, this.bits = n, pe.FOR_BITS.set(n, this), pe.FOR_VALUE.set(e, this);
      }
      getValue() {
        return this.value;
      }
      getBits() {
        return this.bits;
      }
      static fromString(e) {
        switch (e) {
          case "L":
            return pe.L;
          case "M":
            return pe.M;
          case "Q":
            return pe.Q;
          case "H":
            return pe.H;
          default:
            throw new R(e + "not available");
        }
      }
      toString() {
        return this.stringValue;
      }
      equals(e) {
        if (!(e instanceof pe))
          return !1;
        const t = e;
        return this.value === t.value;
      }
      /**
       * @param bits int containing the two bits encoding a QR Code's error correction level
       * @return ErrorCorrectionLevel representing the encoded error correction level
       */
      static forBits(e) {
        if (e < 0 || e >= pe.FOR_BITS.size)
          throw new D();
        return pe.FOR_BITS.get(e);
      }
    }
    pe.FOR_BITS = /* @__PURE__ */ new Map(), pe.FOR_VALUE = /* @__PURE__ */ new Map(), pe.L = new pe(jt.L, "L", 1), pe.M = new pe(jt.M, "M", 0), pe.Q = new pe(jt.Q, "Q", 3), pe.H = new pe(jt.H, "H", 2);
    class Ze {
      constructor(e) {
        this.errorCorrectionLevel = pe.forBits(e >> 3 & 3), this.dataMask = /*(byte) */
        e & 7;
      }
      static numBitsDiffering(e, t) {
        return j.bitCount(e ^ t);
      }
      /**
       * @param maskedFormatInfo1 format info indicator, with mask still applied
       * @param maskedFormatInfo2 second copy of same info; both are checked at the same time
       *  to establish best match
       * @return information about the format it specifies, or {@code null}
       *  if doesn't seem to match any known pattern
       */
      static decodeFormatInformation(e, t) {
        const n = Ze.doDecodeFormatInformation(e, t);
        return n !== null ? n : Ze.doDecodeFormatInformation(e ^ Ze.FORMAT_INFO_MASK_QR, t ^ Ze.FORMAT_INFO_MASK_QR);
      }
      static doDecodeFormatInformation(e, t) {
        let n = Number.MAX_SAFE_INTEGER, r = 0;
        for (const i of Ze.FORMAT_INFO_DECODE_LOOKUP) {
          const s = i[0];
          if (s === e || s === t)
            return new Ze(i[1]);
          let o = Ze.numBitsDiffering(e, s);
          o < n && (r = i[1], n = o), e !== t && (o = Ze.numBitsDiffering(t, s), o < n && (r = i[1], n = o));
        }
        return n <= 3 ? new Ze(r) : null;
      }
      getErrorCorrectionLevel() {
        return this.errorCorrectionLevel;
      }
      getDataMask() {
        return this.dataMask;
      }
      /*@Override*/
      hashCode() {
        return this.errorCorrectionLevel.getBits() << 3 | this.dataMask;
      }
      /*@Override*/
      equals(e) {
        if (!(e instanceof Ze))
          return !1;
        const t = e;
        return this.errorCorrectionLevel === t.errorCorrectionLevel && this.dataMask === t.dataMask;
      }
    }
    Ze.FORMAT_INFO_MASK_QR = 21522, Ze.FORMAT_INFO_DECODE_LOOKUP = [
      Int32Array.from([21522, 0]),
      Int32Array.from([20773, 1]),
      Int32Array.from([24188, 2]),
      Int32Array.from([23371, 3]),
      Int32Array.from([17913, 4]),
      Int32Array.from([16590, 5]),
      Int32Array.from([20375, 6]),
      Int32Array.from([19104, 7]),
      Int32Array.from([30660, 8]),
      Int32Array.from([29427, 9]),
      Int32Array.from([32170, 10]),
      Int32Array.from([30877, 11]),
      Int32Array.from([26159, 12]),
      Int32Array.from([25368, 13]),
      Int32Array.from([27713, 14]),
      Int32Array.from([26998, 15]),
      Int32Array.from([5769, 16]),
      Int32Array.from([5054, 17]),
      Int32Array.from([7399, 18]),
      Int32Array.from([6608, 19]),
      Int32Array.from([1890, 20]),
      Int32Array.from([597, 21]),
      Int32Array.from([3340, 22]),
      Int32Array.from([2107, 23]),
      Int32Array.from([13663, 24]),
      Int32Array.from([12392, 25]),
      Int32Array.from([16177, 26]),
      Int32Array.from([14854, 27]),
      Int32Array.from([9396, 28]),
      Int32Array.from([8579, 29]),
      Int32Array.from([11994, 30]),
      Int32Array.from([11245, 31])
    ];
    class S {
      constructor(e, ...t) {
        this.ecCodewordsPerBlock = e, this.ecBlocks = t;
      }
      getECCodewordsPerBlock() {
        return this.ecCodewordsPerBlock;
      }
      getNumBlocks() {
        let e = 0;
        const t = this.ecBlocks;
        for (const n of t)
          e += n.getCount();
        return e;
      }
      getTotalECCodewords() {
        return this.ecCodewordsPerBlock * this.getNumBlocks();
      }
      getECBlocks() {
        return this.ecBlocks;
      }
    }
    class A {
      constructor(e, t) {
        this.count = e, this.dataCodewords = t;
      }
      getCount() {
        return this.count;
      }
      getDataCodewords() {
        return this.dataCodewords;
      }
    }
    class X {
      constructor(e, t, ...n) {
        this.versionNumber = e, this.alignmentPatternCenters = t, this.ecBlocks = n;
        let r = 0;
        const i = n[0].getECCodewordsPerBlock(), s = n[0].getECBlocks();
        for (const o of s)
          r += o.getCount() * (o.getDataCodewords() + i);
        this.totalCodewords = r;
      }
      getVersionNumber() {
        return this.versionNumber;
      }
      getAlignmentPatternCenters() {
        return this.alignmentPatternCenters;
      }
      getTotalCodewords() {
        return this.totalCodewords;
      }
      getDimensionForVersion() {
        return 17 + 4 * this.versionNumber;
      }
      getECBlocksForLevel(e) {
        return this.ecBlocks[e.getValue()];
      }
      /**
       * <p>Deduces version information purely from QR Code dimensions.</p>
       *
       * @param dimension dimension in modules
       * @return Version for a QR Code of that dimension
       * @throws FormatException if dimension is not 1 mod 4
       */
      static getProvisionalVersionForDimension(e) {
        if (e % 4 !== 1)
          throw new U();
        try {
          return this.getVersionForNumber((e - 17) / 4);
        } catch {
          throw new U();
        }
      }
      static getVersionForNumber(e) {
        if (e < 1 || e > 40)
          throw new D();
        return X.VERSIONS[e - 1];
      }
      static decodeVersionInformation(e) {
        let t = Number.MAX_SAFE_INTEGER, n = 0;
        for (let r = 0; r < X.VERSION_DECODE_INFO.length; r++) {
          const i = X.VERSION_DECODE_INFO[r];
          if (i === e)
            return X.getVersionForNumber(r + 7);
          const s = Ze.numBitsDiffering(e, i);
          s < t && (n = r + 7, t = s);
        }
        return t <= 3 ? X.getVersionForNumber(n) : null;
      }
      /**
       * See ISO 18004:2006 Annex E
       */
      buildFunctionPattern() {
        const e = this.getDimensionForVersion(), t = new Fe(e);
        t.setRegion(0, 0, 9, 9), t.setRegion(e - 8, 0, 8, 9), t.setRegion(0, e - 8, 9, 8);
        const n = this.alignmentPatternCenters.length;
        for (let r = 0; r < n; r++) {
          const i = this.alignmentPatternCenters[r] - 2;
          for (let s = 0; s < n; s++)
            r === 0 && (s === 0 || s === n - 1) || r === n - 1 && s === 0 || t.setRegion(this.alignmentPatternCenters[s] - 2, i, 5, 5);
        }
        return t.setRegion(6, 9, 1, e - 17), t.setRegion(9, 6, e - 17, 1), this.versionNumber > 6 && (t.setRegion(e - 11, 0, 3, 6), t.setRegion(0, e - 11, 6, 3)), t;
      }
      /*@Override*/
      toString() {
        return "" + this.versionNumber;
      }
    }
    X.VERSION_DECODE_INFO = Int32Array.from([
      31892,
      34236,
      39577,
      42195,
      48118,
      51042,
      55367,
      58893,
      63784,
      68472,
      70749,
      76311,
      79154,
      84390,
      87683,
      92361,
      96236,
      102084,
      102881,
      110507,
      110734,
      117786,
      119615,
      126325,
      127568,
      133589,
      136944,
      141498,
      145311,
      150283,
      152622,
      158308,
      161089,
      167017
    ]), X.VERSIONS = [
      new X(1, new Int32Array(0), new S(7, new A(1, 19)), new S(10, new A(1, 16)), new S(13, new A(1, 13)), new S(17, new A(1, 9))),
      new X(2, Int32Array.from([6, 18]), new S(10, new A(1, 34)), new S(16, new A(1, 28)), new S(22, new A(1, 22)), new S(28, new A(1, 16))),
      new X(3, Int32Array.from([6, 22]), new S(15, new A(1, 55)), new S(26, new A(1, 44)), new S(18, new A(2, 17)), new S(22, new A(2, 13))),
      new X(4, Int32Array.from([6, 26]), new S(20, new A(1, 80)), new S(18, new A(2, 32)), new S(26, new A(2, 24)), new S(16, new A(4, 9))),
      new X(5, Int32Array.from([6, 30]), new S(26, new A(1, 108)), new S(24, new A(2, 43)), new S(18, new A(2, 15), new A(2, 16)), new S(22, new A(2, 11), new A(2, 12))),
      new X(6, Int32Array.from([6, 34]), new S(18, new A(2, 68)), new S(16, new A(4, 27)), new S(24, new A(4, 19)), new S(28, new A(4, 15))),
      new X(7, Int32Array.from([6, 22, 38]), new S(20, new A(2, 78)), new S(18, new A(4, 31)), new S(18, new A(2, 14), new A(4, 15)), new S(26, new A(4, 13), new A(1, 14))),
      new X(8, Int32Array.from([6, 24, 42]), new S(24, new A(2, 97)), new S(22, new A(2, 38), new A(2, 39)), new S(22, new A(4, 18), new A(2, 19)), new S(26, new A(4, 14), new A(2, 15))),
      new X(9, Int32Array.from([6, 26, 46]), new S(30, new A(2, 116)), new S(22, new A(3, 36), new A(2, 37)), new S(20, new A(4, 16), new A(4, 17)), new S(24, new A(4, 12), new A(4, 13))),
      new X(10, Int32Array.from([6, 28, 50]), new S(18, new A(2, 68), new A(2, 69)), new S(26, new A(4, 43), new A(1, 44)), new S(24, new A(6, 19), new A(2, 20)), new S(28, new A(6, 15), new A(2, 16))),
      new X(11, Int32Array.from([6, 30, 54]), new S(20, new A(4, 81)), new S(30, new A(1, 50), new A(4, 51)), new S(28, new A(4, 22), new A(4, 23)), new S(24, new A(3, 12), new A(8, 13))),
      new X(12, Int32Array.from([6, 32, 58]), new S(24, new A(2, 92), new A(2, 93)), new S(22, new A(6, 36), new A(2, 37)), new S(26, new A(4, 20), new A(6, 21)), new S(28, new A(7, 14), new A(4, 15))),
      new X(13, Int32Array.from([6, 34, 62]), new S(26, new A(4, 107)), new S(22, new A(8, 37), new A(1, 38)), new S(24, new A(8, 20), new A(4, 21)), new S(22, new A(12, 11), new A(4, 12))),
      new X(14, Int32Array.from([6, 26, 46, 66]), new S(30, new A(3, 115), new A(1, 116)), new S(24, new A(4, 40), new A(5, 41)), new S(20, new A(11, 16), new A(5, 17)), new S(24, new A(11, 12), new A(5, 13))),
      new X(15, Int32Array.from([6, 26, 48, 70]), new S(22, new A(5, 87), new A(1, 88)), new S(24, new A(5, 41), new A(5, 42)), new S(30, new A(5, 24), new A(7, 25)), new S(24, new A(11, 12), new A(7, 13))),
      new X(16, Int32Array.from([6, 26, 50, 74]), new S(24, new A(5, 98), new A(1, 99)), new S(28, new A(7, 45), new A(3, 46)), new S(24, new A(15, 19), new A(2, 20)), new S(30, new A(3, 15), new A(13, 16))),
      new X(17, Int32Array.from([6, 30, 54, 78]), new S(28, new A(1, 107), new A(5, 108)), new S(28, new A(10, 46), new A(1, 47)), new S(28, new A(1, 22), new A(15, 23)), new S(28, new A(2, 14), new A(17, 15))),
      new X(18, Int32Array.from([6, 30, 56, 82]), new S(30, new A(5, 120), new A(1, 121)), new S(26, new A(9, 43), new A(4, 44)), new S(28, new A(17, 22), new A(1, 23)), new S(28, new A(2, 14), new A(19, 15))),
      new X(19, Int32Array.from([6, 30, 58, 86]), new S(28, new A(3, 113), new A(4, 114)), new S(26, new A(3, 44), new A(11, 45)), new S(26, new A(17, 21), new A(4, 22)), new S(26, new A(9, 13), new A(16, 14))),
      new X(20, Int32Array.from([6, 34, 62, 90]), new S(28, new A(3, 107), new A(5, 108)), new S(26, new A(3, 41), new A(13, 42)), new S(30, new A(15, 24), new A(5, 25)), new S(28, new A(15, 15), new A(10, 16))),
      new X(21, Int32Array.from([6, 28, 50, 72, 94]), new S(28, new A(4, 116), new A(4, 117)), new S(26, new A(17, 42)), new S(28, new A(17, 22), new A(6, 23)), new S(30, new A(19, 16), new A(6, 17))),
      new X(22, Int32Array.from([6, 26, 50, 74, 98]), new S(28, new A(2, 111), new A(7, 112)), new S(28, new A(17, 46)), new S(30, new A(7, 24), new A(16, 25)), new S(24, new A(34, 13))),
      new X(23, Int32Array.from([6, 30, 54, 78, 102]), new S(30, new A(4, 121), new A(5, 122)), new S(28, new A(4, 47), new A(14, 48)), new S(30, new A(11, 24), new A(14, 25)), new S(30, new A(16, 15), new A(14, 16))),
      new X(24, Int32Array.from([6, 28, 54, 80, 106]), new S(30, new A(6, 117), new A(4, 118)), new S(28, new A(6, 45), new A(14, 46)), new S(30, new A(11, 24), new A(16, 25)), new S(30, new A(30, 16), new A(2, 17))),
      new X(25, Int32Array.from([6, 32, 58, 84, 110]), new S(26, new A(8, 106), new A(4, 107)), new S(28, new A(8, 47), new A(13, 48)), new S(30, new A(7, 24), new A(22, 25)), new S(30, new A(22, 15), new A(13, 16))),
      new X(26, Int32Array.from([6, 30, 58, 86, 114]), new S(28, new A(10, 114), new A(2, 115)), new S(28, new A(19, 46), new A(4, 47)), new S(28, new A(28, 22), new A(6, 23)), new S(30, new A(33, 16), new A(4, 17))),
      new X(27, Int32Array.from([6, 34, 62, 90, 118]), new S(30, new A(8, 122), new A(4, 123)), new S(28, new A(22, 45), new A(3, 46)), new S(30, new A(8, 23), new A(26, 24)), new S(30, new A(12, 15), new A(28, 16))),
      new X(28, Int32Array.from([6, 26, 50, 74, 98, 122]), new S(30, new A(3, 117), new A(10, 118)), new S(28, new A(3, 45), new A(23, 46)), new S(30, new A(4, 24), new A(31, 25)), new S(30, new A(11, 15), new A(31, 16))),
      new X(29, Int32Array.from([6, 30, 54, 78, 102, 126]), new S(30, new A(7, 116), new A(7, 117)), new S(28, new A(21, 45), new A(7, 46)), new S(30, new A(1, 23), new A(37, 24)), new S(30, new A(19, 15), new A(26, 16))),
      new X(30, Int32Array.from([6, 26, 52, 78, 104, 130]), new S(30, new A(5, 115), new A(10, 116)), new S(28, new A(19, 47), new A(10, 48)), new S(30, new A(15, 24), new A(25, 25)), new S(30, new A(23, 15), new A(25, 16))),
      new X(31, Int32Array.from([6, 30, 56, 82, 108, 134]), new S(30, new A(13, 115), new A(3, 116)), new S(28, new A(2, 46), new A(29, 47)), new S(30, new A(42, 24), new A(1, 25)), new S(30, new A(23, 15), new A(28, 16))),
      new X(32, Int32Array.from([6, 34, 60, 86, 112, 138]), new S(30, new A(17, 115)), new S(28, new A(10, 46), new A(23, 47)), new S(30, new A(10, 24), new A(35, 25)), new S(30, new A(19, 15), new A(35, 16))),
      new X(33, Int32Array.from([6, 30, 58, 86, 114, 142]), new S(30, new A(17, 115), new A(1, 116)), new S(28, new A(14, 46), new A(21, 47)), new S(30, new A(29, 24), new A(19, 25)), new S(30, new A(11, 15), new A(46, 16))),
      new X(34, Int32Array.from([6, 34, 62, 90, 118, 146]), new S(30, new A(13, 115), new A(6, 116)), new S(28, new A(14, 46), new A(23, 47)), new S(30, new A(44, 24), new A(7, 25)), new S(30, new A(59, 16), new A(1, 17))),
      new X(35, Int32Array.from([6, 30, 54, 78, 102, 126, 150]), new S(30, new A(12, 121), new A(7, 122)), new S(28, new A(12, 47), new A(26, 48)), new S(30, new A(39, 24), new A(14, 25)), new S(30, new A(22, 15), new A(41, 16))),
      new X(36, Int32Array.from([6, 24, 50, 76, 102, 128, 154]), new S(30, new A(6, 121), new A(14, 122)), new S(28, new A(6, 47), new A(34, 48)), new S(30, new A(46, 24), new A(10, 25)), new S(30, new A(2, 15), new A(64, 16))),
      new X(37, Int32Array.from([6, 28, 54, 80, 106, 132, 158]), new S(30, new A(17, 122), new A(4, 123)), new S(28, new A(29, 46), new A(14, 47)), new S(30, new A(49, 24), new A(10, 25)), new S(30, new A(24, 15), new A(46, 16))),
      new X(38, Int32Array.from([6, 32, 58, 84, 110, 136, 162]), new S(30, new A(4, 122), new A(18, 123)), new S(28, new A(13, 46), new A(32, 47)), new S(30, new A(48, 24), new A(14, 25)), new S(30, new A(42, 15), new A(32, 16))),
      new X(39, Int32Array.from([6, 26, 54, 82, 110, 138, 166]), new S(30, new A(20, 117), new A(4, 118)), new S(28, new A(40, 47), new A(7, 48)), new S(30, new A(43, 24), new A(22, 25)), new S(30, new A(10, 15), new A(67, 16))),
      new X(40, Int32Array.from([6, 30, 58, 86, 114, 142, 170]), new S(30, new A(19, 118), new A(6, 119)), new S(28, new A(18, 47), new A(31, 48)), new S(30, new A(34, 24), new A(34, 25)), new S(30, new A(20, 15), new A(61, 16)))
    ];
    var Ve;
    (function(u) {
      u[u.DATA_MASK_000 = 0] = "DATA_MASK_000", u[u.DATA_MASK_001 = 1] = "DATA_MASK_001", u[u.DATA_MASK_010 = 2] = "DATA_MASK_010", u[u.DATA_MASK_011 = 3] = "DATA_MASK_011", u[u.DATA_MASK_100 = 4] = "DATA_MASK_100", u[u.DATA_MASK_101 = 5] = "DATA_MASK_101", u[u.DATA_MASK_110 = 6] = "DATA_MASK_110", u[u.DATA_MASK_111 = 7] = "DATA_MASK_111";
    })(Ve || (Ve = {}));
    class lt {
      // See ISO 18004:2006 6.8.1
      constructor(e, t) {
        this.value = e, this.isMasked = t;
      }
      // End of enum constants.
      /**
       * <p>Implementations of this method reverse the data masking process applied to a QR Code and
       * make its bits ready to read.</p>
       *
       * @param bits representation of QR Code bits
       * @param dimension dimension of QR Code, represented by bits, being unmasked
       */
      unmaskBitMatrix(e, t) {
        for (let n = 0; n < t; n++)
          for (let r = 0; r < t; r++)
            this.isMasked(n, r) && e.flip(r, n);
      }
    }
    lt.values = /* @__PURE__ */ new Map([
      /**
       * 000: mask bits for which (x + y) mod 2 == 0
       */
      [Ve.DATA_MASK_000, new lt(Ve.DATA_MASK_000, (u, e) => (u + e & 1) === 0)],
      /**
       * 001: mask bits for which x mod 2 == 0
       */
      [Ve.DATA_MASK_001, new lt(Ve.DATA_MASK_001, (u, e) => (u & 1) === 0)],
      /**
       * 010: mask bits for which y mod 3 == 0
       */
      [Ve.DATA_MASK_010, new lt(Ve.DATA_MASK_010, (u, e) => e % 3 === 0)],
      /**
       * 011: mask bits for which (x + y) mod 3 == 0
       */
      [Ve.DATA_MASK_011, new lt(Ve.DATA_MASK_011, (u, e) => (u + e) % 3 === 0)],
      /**
       * 100: mask bits for which (x/2 + y/3) mod 2 == 0
       */
      [Ve.DATA_MASK_100, new lt(Ve.DATA_MASK_100, (u, e) => (Math.floor(u / 2) + Math.floor(e / 3) & 1) === 0)],
      /**
       * 101: mask bits for which xy mod 2 + xy mod 3 == 0
       * equivalently, such that xy mod 6 == 0
       */
      [Ve.DATA_MASK_101, new lt(Ve.DATA_MASK_101, (u, e) => u * e % 6 === 0)],
      /**
       * 110: mask bits for which (xy mod 2 + xy mod 3) mod 2 == 0
       * equivalently, such that xy mod 6 < 3
       */
      [Ve.DATA_MASK_110, new lt(Ve.DATA_MASK_110, (u, e) => u * e % 6 < 3)],
      /**
       * 111: mask bits for which ((x+y)mod 2 + xy mod 3) mod 2 == 0
       * equivalently, such that (x + y + xy mod 3) mod 2 == 0
       */
      [Ve.DATA_MASK_111, new lt(Ve.DATA_MASK_111, (u, e) => (u + e + u * e % 3 & 1) === 0)]
    ]);
    class qr {
      /**
       * @param bitMatrix {@link BitMatrix} to parse
       * @throws FormatException if dimension is not >= 21 and 1 mod 4
       */
      constructor(e) {
        const t = e.getHeight();
        if (t < 21 || (t & 3) !== 1)
          throw new U();
        this.bitMatrix = e;
      }
      /**
       * <p>Reads format information from one of its two locations within the QR Code.</p>
       *
       * @return {@link FormatInformation} encapsulating the QR Code's format info
       * @throws FormatException if both format information locations cannot be parsed as
       * the valid encoding of format information
       */
      readFormatInformation() {
        if (this.parsedFormatInfo !== null && this.parsedFormatInfo !== void 0)
          return this.parsedFormatInfo;
        let e = 0;
        for (let i = 0; i < 6; i++)
          e = this.copyBit(i, 8, e);
        e = this.copyBit(7, 8, e), e = this.copyBit(8, 8, e), e = this.copyBit(8, 7, e);
        for (let i = 5; i >= 0; i--)
          e = this.copyBit(8, i, e);
        const t = this.bitMatrix.getHeight();
        let n = 0;
        const r = t - 7;
        for (let i = t - 1; i >= r; i--)
          n = this.copyBit(8, i, n);
        for (let i = t - 8; i < t; i++)
          n = this.copyBit(i, 8, n);
        if (this.parsedFormatInfo = Ze.decodeFormatInformation(e, n), this.parsedFormatInfo !== null)
          return this.parsedFormatInfo;
        throw new U();
      }
      /**
       * <p>Reads version information from one of its two locations within the QR Code.</p>
       *
       * @return {@link Version} encapsulating the QR Code's version
       * @throws FormatException if both version information locations cannot be parsed as
       * the valid encoding of version information
       */
      readVersion() {
        if (this.parsedVersion !== null && this.parsedVersion !== void 0)
          return this.parsedVersion;
        const e = this.bitMatrix.getHeight(), t = Math.floor((e - 17) / 4);
        if (t <= 6)
          return X.getVersionForNumber(t);
        let n = 0;
        const r = e - 11;
        for (let s = 5; s >= 0; s--)
          for (let o = e - 9; o >= r; o--)
            n = this.copyBit(o, s, n);
        let i = X.decodeVersionInformation(n);
        if (i !== null && i.getDimensionForVersion() === e)
          return this.parsedVersion = i, i;
        n = 0;
        for (let s = 5; s >= 0; s--)
          for (let o = e - 9; o >= r; o--)
            n = this.copyBit(s, o, n);
        if (i = X.decodeVersionInformation(n), i !== null && i.getDimensionForVersion() === e)
          return this.parsedVersion = i, i;
        throw new U();
      }
      copyBit(e, t, n) {
        return (this.isMirror ? this.bitMatrix.get(t, e) : this.bitMatrix.get(e, t)) ? n << 1 | 1 : n << 1;
      }
      /**
       * <p>Reads the bits in the {@link BitMatrix} representing the finder pattern in the
       * correct order in order to reconstruct the codewords bytes contained within the
       * QR Code.</p>
       *
       * @return bytes encoded within the QR Code
       * @throws FormatException if the exact number of bytes expected is not read
       */
      readCodewords() {
        const e = this.readFormatInformation(), t = this.readVersion(), n = lt.values.get(e.getDataMask()), r = this.bitMatrix.getHeight();
        n.unmaskBitMatrix(this.bitMatrix, r);
        const i = t.buildFunctionPattern();
        let s = !0;
        const o = new Uint8Array(t.getTotalCodewords());
        let a = 0, l = 0, c = 0;
        for (let f = r - 1; f > 0; f -= 2) {
          f === 6 && f--;
          for (let d = 0; d < r; d++) {
            const C = s ? r - 1 - d : d;
            for (let p = 0; p < 2; p++)
              i.get(f - p, C) || (c++, l <<= 1, this.bitMatrix.get(f - p, C) && (l |= 1), c === 8 && (o[a++] = /*(byte) */
              l, c = 0, l = 0));
          }
          s = !s;
        }
        if (a !== t.getTotalCodewords())
          throw new U();
        return o;
      }
      /**
       * Revert the mask removal done while reading the code words. The bit matrix should revert to its original state.
       */
      remask() {
        if (this.parsedFormatInfo === null)
          return;
        const e = lt.values[this.parsedFormatInfo.getDataMask()], t = this.bitMatrix.getHeight();
        e.unmaskBitMatrix(this.bitMatrix, t);
      }
      /**
       * Prepare the parser for a mirrored operation.
       * This flag has effect only on the {@link #readFormatInformation()} and the
       * {@link #readVersion()}. Before proceeding with {@link #readCodewords()} the
       * {@link #mirror()} method should be called.
       *
       * @param mirror Whether to read version and format information mirrored.
       */
      setMirror(e) {
        this.parsedVersion = null, this.parsedFormatInfo = null, this.isMirror = e;
      }
      /** Mirror the bit matrix in order to attempt a second reading. */
      mirror() {
        const e = this.bitMatrix;
        for (let t = 0, n = e.getWidth(); t < n; t++)
          for (let r = t + 1, i = e.getHeight(); r < i; r++)
            e.get(t, r) !== e.get(r, t) && (e.flip(r, t), e.flip(t, r));
      }
    }
    class Yn {
      constructor(e, t) {
        this.numDataCodewords = e, this.codewords = t;
      }
      /**
       * <p>When QR Codes use multiple data blocks, they are actually interleaved.
       * That is, the first byte of data block 1 to n is written, then the second bytes, and so on. This
       * method will separate the data into original blocks.</p>
       *
       * @param rawCodewords bytes as read directly from the QR Code
       * @param version version of the QR Code
       * @param ecLevel error-correction level of the QR Code
       * @return DataBlocks containing original bytes, "de-interleaved" from representation in the
       *         QR Code
       */
      static getDataBlocks(e, t, n) {
        if (e.length !== t.getTotalCodewords())
          throw new D();
        const r = t.getECBlocksForLevel(n);
        let i = 0;
        const s = r.getECBlocks();
        for (const p of s)
          i += p.getCount();
        const o = new Array(i);
        let a = 0;
        for (const p of s)
          for (let b = 0; b < p.getCount(); b++) {
            const y = p.getDataCodewords(), _ = r.getECCodewordsPerBlock() + y;
            o[a++] = new Yn(y, new Uint8Array(_));
          }
        const l = o[0].codewords.length;
        let c = o.length - 1;
        for (; c >= 0 && o[c].codewords.length !== l; )
          c--;
        c++;
        const f = l - r.getECCodewordsPerBlock();
        let d = 0;
        for (let p = 0; p < f; p++)
          for (let b = 0; b < a; b++)
            o[b].codewords[p] = e[d++];
        for (let p = c; p < a; p++)
          o[p].codewords[f] = e[d++];
        const C = o[0].codewords.length;
        for (let p = f; p < C; p++)
          for (let b = 0; b < a; b++) {
            const y = b < c ? p : p + 1;
            o[b].codewords[y] = e[d++];
          }
        return o;
      }
      getNumDataCodewords() {
        return this.numDataCodewords;
      }
      getCodewords() {
        return this.codewords;
      }
    }
    var ct;
    (function(u) {
      u[u.TERMINATOR = 0] = "TERMINATOR", u[u.NUMERIC = 1] = "NUMERIC", u[u.ALPHANUMERIC = 2] = "ALPHANUMERIC", u[u.STRUCTURED_APPEND = 3] = "STRUCTURED_APPEND", u[u.BYTE = 4] = "BYTE", u[u.ECI = 5] = "ECI", u[u.KANJI = 6] = "KANJI", u[u.FNC1_FIRST_POSITION = 7] = "FNC1_FIRST_POSITION", u[u.FNC1_SECOND_POSITION = 8] = "FNC1_SECOND_POSITION", u[u.HANZI = 9] = "HANZI";
    })(ct || (ct = {}));
    class Y {
      constructor(e, t, n, r) {
        this.value = e, this.stringValue = t, this.characterCountBitsForVersions = n, this.bits = r, Y.FOR_BITS.set(r, this), Y.FOR_VALUE.set(e, this);
      }
      /**
       * @param bits four bits encoding a QR Code data mode
       * @return Mode encoded by these bits
       * @throws IllegalArgumentException if bits do not correspond to a known mode
       */
      static forBits(e) {
        const t = Y.FOR_BITS.get(e);
        if (t === void 0)
          throw new D();
        return t;
      }
      /**
       * @param version version in question
       * @return number of bits used, in this QR Code symbol {@link Version}, to encode the
       *         count of characters that will follow encoded in this Mode
       */
      getCharacterCountBits(e) {
        const t = e.getVersionNumber();
        let n;
        return t <= 9 ? n = 0 : t <= 26 ? n = 1 : n = 2, this.characterCountBitsForVersions[n];
      }
      getValue() {
        return this.value;
      }
      getBits() {
        return this.bits;
      }
      equals(e) {
        if (!(e instanceof Y))
          return !1;
        const t = e;
        return this.value === t.value;
      }
      toString() {
        return this.stringValue;
      }
    }
    Y.FOR_BITS = /* @__PURE__ */ new Map(), Y.FOR_VALUE = /* @__PURE__ */ new Map(), Y.TERMINATOR = new Y(ct.TERMINATOR, "TERMINATOR", Int32Array.from([0, 0, 0]), 0), Y.NUMERIC = new Y(ct.NUMERIC, "NUMERIC", Int32Array.from([10, 12, 14]), 1), Y.ALPHANUMERIC = new Y(ct.ALPHANUMERIC, "ALPHANUMERIC", Int32Array.from([9, 11, 13]), 2), Y.STRUCTURED_APPEND = new Y(ct.STRUCTURED_APPEND, "STRUCTURED_APPEND", Int32Array.from([0, 0, 0]), 3), Y.BYTE = new Y(ct.BYTE, "BYTE", Int32Array.from([8, 16, 16]), 4), Y.ECI = new Y(ct.ECI, "ECI", Int32Array.from([0, 0, 0]), 7), Y.KANJI = new Y(ct.KANJI, "KANJI", Int32Array.from([8, 10, 12]), 8), Y.FNC1_FIRST_POSITION = new Y(ct.FNC1_FIRST_POSITION, "FNC1_FIRST_POSITION", Int32Array.from([0, 0, 0]), 5), Y.FNC1_SECOND_POSITION = new Y(ct.FNC1_SECOND_POSITION, "FNC1_SECOND_POSITION", Int32Array.from([0, 0, 0]), 9), Y.HANZI = new Y(ct.HANZI, "HANZI", Int32Array.from([8, 10, 12]), 13);
    class Se {
      static decode(e, t, n, r) {
        const i = new Xn(e);
        let s = new ge();
        const o = new Array();
        let a = -1, l = -1;
        try {
          let c = null, f = !1, d;
          do {
            if (i.available() < 4)
              d = Y.TERMINATOR;
            else {
              const C = i.readBits(4);
              d = Y.forBits(C);
            }
            switch (d) {
              case Y.TERMINATOR:
                break;
              case Y.FNC1_FIRST_POSITION:
              case Y.FNC1_SECOND_POSITION:
                f = !0;
                break;
              case Y.STRUCTURED_APPEND:
                if (i.available() < 16)
                  throw new U();
                a = i.readBits(8), l = i.readBits(8);
                break;
              case Y.ECI:
                const C = Se.parseECIValue(i);
                if (c = k.getCharacterSetECIByValue(C), c === null)
                  throw new U();
                break;
              case Y.HANZI:
                const p = i.readBits(4), b = i.readBits(d.getCharacterCountBits(t));
                p === Se.GB2312_SUBSET && Se.decodeHanziSegment(i, s, b);
                break;
              default:
                const y = i.readBits(d.getCharacterCountBits(t));
                switch (d) {
                  case Y.NUMERIC:
                    Se.decodeNumericSegment(i, s, y);
                    break;
                  case Y.ALPHANUMERIC:
                    Se.decodeAlphanumericSegment(i, s, y, f);
                    break;
                  case Y.BYTE:
                    Se.decodeByteSegment(i, s, y, c, o, r);
                    break;
                  case Y.KANJI:
                    Se.decodeKanjiSegment(i, s, y);
                    break;
                  default:
                    throw new U();
                }
                break;
            }
          } while (d !== Y.TERMINATOR);
        } catch {
          throw new U();
        }
        return new nn(e, s.toString(), o.length === 0 ? null : o, n === null ? null : n.toString(), a, l);
      }
      /**
       * See specification GBT 18284-2000
       */
      static decodeHanziSegment(e, t, n) {
        if (n * 13 > e.available())
          throw new U();
        const r = new Uint8Array(2 * n);
        let i = 0;
        for (; n > 0; ) {
          const s = e.readBits(13);
          let o = s / 96 << 8 & 4294967295 | s % 96;
          o < 959 ? o += 41377 : o += 42657, r[i] = /*(byte) */
          o >> 8 & 255, r[i + 1] = /*(byte) */
          o & 255, i += 2, n--;
        }
        try {
          t.append(Je.decode(r, q.GB2312));
        } catch (s) {
          throw new U(s);
        }
      }
      static decodeKanjiSegment(e, t, n) {
        if (n * 13 > e.available())
          throw new U();
        const r = new Uint8Array(2 * n);
        let i = 0;
        for (; n > 0; ) {
          const s = e.readBits(13);
          let o = s / 192 << 8 & 4294967295 | s % 192;
          o < 7936 ? o += 33088 : o += 49472, r[i] = /*(byte) */
          o >> 8, r[i + 1] = /*(byte) */
          o, i += 2, n--;
        }
        try {
          t.append(Je.decode(r, q.SHIFT_JIS));
        } catch (s) {
          throw new U(s);
        }
      }
      static decodeByteSegment(e, t, n, r, i, s) {
        if (8 * n > e.available())
          throw new U();
        const o = new Uint8Array(n);
        for (let l = 0; l < n; l++)
          o[l] = /*(byte) */
          e.readBits(8);
        let a;
        r === null ? a = q.guessEncoding(o, s) : a = r.getName();
        try {
          t.append(Je.decode(o, a));
        } catch (l) {
          throw new U(l);
        }
        i.push(o);
      }
      static toAlphaNumericChar(e) {
        if (e >= Se.ALPHANUMERIC_CHARS.length)
          throw new U();
        return Se.ALPHANUMERIC_CHARS[e];
      }
      static decodeAlphanumericSegment(e, t, n, r) {
        const i = t.length();
        for (; n > 1; ) {
          if (e.available() < 11)
            throw new U();
          const s = e.readBits(11);
          t.append(Se.toAlphaNumericChar(Math.floor(s / 45))), t.append(Se.toAlphaNumericChar(s % 45)), n -= 2;
        }
        if (n === 1) {
          if (e.available() < 6)
            throw new U();
          t.append(Se.toAlphaNumericChar(e.readBits(6)));
        }
        if (r)
          for (let s = i; s < t.length(); s++)
            t.charAt(s) === "%" && (s < t.length() - 1 && t.charAt(s + 1) === "%" ? t.deleteCharAt(s + 1) : t.setCharAt(s, ""));
      }
      static decodeNumericSegment(e, t, n) {
        for (; n >= 3; ) {
          if (e.available() < 10)
            throw new U();
          const r = e.readBits(10);
          if (r >= 1e3)
            throw new U();
          t.append(Se.toAlphaNumericChar(Math.floor(r / 100))), t.append(Se.toAlphaNumericChar(Math.floor(r / 10) % 10)), t.append(Se.toAlphaNumericChar(r % 10)), n -= 3;
        }
        if (n === 2) {
          if (e.available() < 7)
            throw new U();
          const r = e.readBits(7);
          if (r >= 100)
            throw new U();
          t.append(Se.toAlphaNumericChar(Math.floor(r / 10))), t.append(Se.toAlphaNumericChar(r % 10));
        } else if (n === 1) {
          if (e.available() < 4)
            throw new U();
          const r = e.readBits(4);
          if (r >= 10)
            throw new U();
          t.append(Se.toAlphaNumericChar(r));
        }
      }
      static parseECIValue(e) {
        const t = e.readBits(8);
        if (!(t & 128))
          return t & 127;
        if ((t & 192) === 128) {
          const n = e.readBits(8);
          return (t & 63) << 8 & 4294967295 | n;
        }
        if ((t & 224) === 192) {
          const n = e.readBits(16);
          return (t & 31) << 16 & 4294967295 | n;
        }
        throw new U();
      }
    }
    Se.ALPHANUMERIC_CHARS = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:", Se.GB2312_SUBSET = 1;
    class dr {
      constructor(e) {
        this.mirrored = e;
      }
      /**
       * @return true if the QR Code was mirrored.
       */
      isMirrored() {
        return this.mirrored;
      }
      /**
       * Apply the result points' order correction due to mirroring.
       *
       * @param points Array of points to apply mirror correction to.
       */
      applyMirroredCorrection(e) {
        if (!this.mirrored || e === null || e.length < 3)
          return;
        const t = e[0];
        e[0] = e[2], e[2] = t;
      }
    }
    class Qr {
      constructor() {
        this.rsDecoder = new sn(ce.QR_CODE_FIELD_256);
      }
      // public decode(image: boolean[][]): DecoderResult /*throws ChecksumException, FormatException*/ {
      //   return decode(image, null)
      // }
      /**
       * <p>Convenience method that can decode a QR Code represented as a 2D array of booleans.
       * "true" is taken to mean a black module.</p>
       *
       * @param image booleans representing white/black QR Code modules
       * @param hints decoding hints that should be used to influence decoding
       * @return text and bytes encoded within the QR Code
       * @throws FormatException if the QR Code cannot be decoded
       * @throws ChecksumException if error correction fails
       */
      decodeBooleanArray(e, t) {
        return this.decodeBitMatrix(Fe.parseFromBooleanArray(e), t);
      }
      // public decodeBitMatrix(bits: BitMatrix): DecoderResult /*throws ChecksumException, FormatException*/ {
      //   return decode(bits, null)
      // }
      /**
       * <p>Decodes a QR Code represented as a {@link BitMatrix}. A 1 or "true" is taken to mean a black module.</p>
       *
       * @param bits booleans representing white/black QR Code modules
       * @param hints decoding hints that should be used to influence decoding
       * @return text and bytes encoded within the QR Code
       * @throws FormatException if the QR Code cannot be decoded
       * @throws ChecksumException if error correction fails
       */
      decodeBitMatrix(e, t) {
        const n = new qr(e);
        let r = null;
        try {
          return this.decodeBitMatrixParser(n, t);
        } catch (i) {
          r = i;
        }
        try {
          n.remask(), n.setMirror(!0), n.readVersion(), n.readFormatInformation(), n.mirror();
          const i = this.decodeBitMatrixParser(n, t);
          return i.setOther(new dr(!0)), i;
        } catch (i) {
          throw r !== null ? r : i;
        }
      }
      decodeBitMatrixParser(e, t) {
        const n = e.readVersion(), r = e.readFormatInformation().getErrorCorrectionLevel(), i = e.readCodewords(), s = Yn.getDataBlocks(i, n, r);
        let o = 0;
        for (const c of s)
          o += c.getNumDataCodewords();
        const a = new Uint8Array(o);
        let l = 0;
        for (const c of s) {
          const f = c.getCodewords(), d = c.getNumDataCodewords();
          this.correctErrors(f, d);
          for (let C = 0; C < d; C++)
            a[l++] = f[C];
        }
        return Se.decode(a, n, r, t);
      }
      /**
       * <p>Given data and error-correction codewords received, possibly corrupted by errors, attempts to
       * correct the errors in-place using Reed-Solomon error correction.</p>
       *
       * @param codewordBytes data and error correction codewords
       * @param numDataCodewords number of codewords that are data bytes
       * @throws ChecksumException if error correction fails
       */
      correctErrors(e, t) {
        const n = new Int32Array(e);
        try {
          this.rsDecoder.decode(n, e.length - t);
        } catch {
          throw new J();
        }
        for (let r = 0; r < t; r++)
          e[r] = /*(byte) */
          n[r];
      }
    }
    class zn extends W {
      constructor(e, t, n) {
        super(e, t), this.estimatedModuleSize = n;
      }
      /**
       * <p>Determines if this alignment pattern "about equals" an alignment pattern at the stated
       * position and size -- meaning, it is at nearly the same center with nearly the same size.</p>
       */
      aboutEquals(e, t, n) {
        if (Math.abs(t - this.getY()) <= e && Math.abs(n - this.getX()) <= e) {
          const r = Math.abs(e - this.estimatedModuleSize);
          return r <= 1 || r <= this.estimatedModuleSize;
        }
        return !1;
      }
      /**
       * Combines this object's current estimate of a finder pattern position and module size
       * with a new estimate. It returns a new {@code FinderPattern} containing an average of the two.
       */
      combineEstimate(e, t, n) {
        const r = (this.getX() + t) / 2, i = (this.getY() + e) / 2, s = (this.estimatedModuleSize + n) / 2;
        return new zn(r, i, s);
      }
    }
    class yn {
      /**
       * <p>Creates a finder that will look in a portion of the whole image.</p>
       *
       * @param image image to search
       * @param startX left column from which to start searching
       * @param startY top row from which to start searching
       * @param width width of region to search
       * @param height height of region to search
       * @param moduleSize estimated module size so far
       */
      constructor(e, t, n, r, i, s, o) {
        this.image = e, this.startX = t, this.startY = n, this.width = r, this.height = i, this.moduleSize = s, this.resultPointCallback = o, this.possibleCenters = [], this.crossCheckStateCount = new Int32Array(3);
      }
      /**
       * <p>This method attempts to find the bottom-right alignment pattern in the image. It is a bit messy since
       * it's pretty performance-critical and so is written to be fast foremost.</p>
       *
       * @return {@link AlignmentPattern} if found
       * @throws NotFoundException if not found
       */
      find() {
        const e = this.startX, t = this.height, n = this.width, r = e + n, i = this.startY + t / 2, s = new Int32Array(3), o = this.image;
        for (let a = 0; a < t; a++) {
          const l = i + (a & 1 ? -Math.floor((a + 1) / 2) : Math.floor((a + 1) / 2));
          s[0] = 0, s[1] = 0, s[2] = 0;
          let c = e;
          for (; c < r && !o.get(c, l); )
            c++;
          let f = 0;
          for (; c < r; ) {
            if (o.get(c, l))
              if (f === 1)
                s[1]++;
              else if (f === 2) {
                if (this.foundPatternCross(s)) {
                  const d = this.handlePossibleCenter(s, l, c);
                  if (d !== null)
                    return d;
                }
                s[0] = s[2], s[1] = 1, s[2] = 0, f = 1;
              } else
                s[++f]++;
            else
              f === 1 && f++, s[f]++;
            c++;
          }
          if (this.foundPatternCross(s)) {
            const d = this.handlePossibleCenter(s, l, r);
            if (d !== null)
              return d;
          }
        }
        if (this.possibleCenters.length !== 0)
          return this.possibleCenters[0];
        throw new O();
      }
      /**
       * Given a count of black/white/black pixels just seen and an end position,
       * figures the location of the center of this black/white/black run.
       */
      static centerFromEnd(e, t) {
        return t - e[2] - e[1] / 2;
      }
      /**
       * @param stateCount count of black/white/black pixels just read
       * @return true iff the proportions of the counts is close enough to the 1/1/1 ratios
       *         used by alignment patterns to be considered a match
       */
      foundPatternCross(e) {
        const t = this.moduleSize, n = t / 2;
        for (let r = 0; r < 3; r++)
          if (Math.abs(t - e[r]) >= n)
            return !1;
        return !0;
      }
      /**
       * <p>After a horizontal scan finds a potential alignment pattern, this method
       * "cross-checks" by scanning down vertically through the center of the possible
       * alignment pattern to see if the same proportion is detected.</p>
       *
       * @param startI row where an alignment pattern was detected
       * @param centerJ center of the section that appears to cross an alignment pattern
       * @param maxCount maximum reasonable number of modules that should be
       * observed in any reading state, based on the results of the horizontal scan
       * @return vertical center of alignment pattern, or {@link Float#NaN} if not found
       */
      crossCheckVertical(e, t, n, r) {
        const i = this.image, s = i.getHeight(), o = this.crossCheckStateCount;
        o[0] = 0, o[1] = 0, o[2] = 0;
        let a = e;
        for (; a >= 0 && i.get(t, a) && o[1] <= n; )
          o[1]++, a--;
        if (a < 0 || o[1] > n)
          return NaN;
        for (; a >= 0 && !i.get(t, a) && o[0] <= n; )
          o[0]++, a--;
        if (o[0] > n)
          return NaN;
        for (a = e + 1; a < s && i.get(t, a) && o[1] <= n; )
          o[1]++, a++;
        if (a === s || o[1] > n)
          return NaN;
        for (; a < s && !i.get(t, a) && o[2] <= n; )
          o[2]++, a++;
        if (o[2] > n)
          return NaN;
        const l = o[0] + o[1] + o[2];
        return 5 * Math.abs(l - r) >= 2 * r ? NaN : this.foundPatternCross(o) ? yn.centerFromEnd(o, a) : NaN;
      }
      /**
       * <p>This is called when a horizontal scan finds a possible alignment pattern. It will
       * cross check with a vertical scan, and if successful, will see if this pattern had been
       * found on a previous horizontal scan. If so, we consider it confirmed and conclude we have
       * found the alignment pattern.</p>
       *
       * @param stateCount reading state module counts from horizontal scan
       * @param i row where alignment pattern may be found
       * @param j end of possible alignment pattern in row
       * @return {@link AlignmentPattern} if we have found the same pattern twice, or null if not
       */
      handlePossibleCenter(e, t, n) {
        const r = e[0] + e[1] + e[2], i = yn.centerFromEnd(e, n), s = this.crossCheckVertical(
          t,
          /*(int) */
          i,
          2 * e[1],
          r
        );
        if (!isNaN(s)) {
          const o = (e[0] + e[1] + e[2]) / 3;
          for (const l of this.possibleCenters)
            if (l.aboutEquals(o, s, i))
              return l.combineEstimate(s, i, o);
          const a = new zn(i, s, o);
          this.possibleCenters.push(a), this.resultPointCallback !== null && this.resultPointCallback !== void 0 && this.resultPointCallback.foundPossibleResultPoint(a);
        }
        return null;
      }
    }
    class Zn extends W {
      // FinderPattern(posX: number/*float*/, posY: number/*float*/, estimatedModuleSize: number/*float*/) {
      //   this(posX, posY, estimatedModuleSize, 1)
      // }
      constructor(e, t, n, r) {
        super(e, t), this.estimatedModuleSize = n, this.count = r, r === void 0 && (this.count = 1);
      }
      getEstimatedModuleSize() {
        return this.estimatedModuleSize;
      }
      getCount() {
        return this.count;
      }
      /*
      void incrementCount() {
        this.count++
      }
       */
      /**
       * <p>Determines if this finder pattern "about equals" a finder pattern at the stated
       * position and size -- meaning, it is at nearly the same center with nearly the same size.</p>
       */
      aboutEquals(e, t, n) {
        if (Math.abs(t - this.getY()) <= e && Math.abs(n - this.getX()) <= e) {
          const r = Math.abs(e - this.estimatedModuleSize);
          return r <= 1 || r <= this.estimatedModuleSize;
        }
        return !1;
      }
      /**
       * Combines this object's current estimate of a finder pattern position and module size
       * with a new estimate. It returns a new {@code FinderPattern} containing a weighted average
       * based on count.
       */
      combineEstimate(e, t, n) {
        const r = this.count + 1, i = (this.count * this.getX() + t) / r, s = (this.count * this.getY() + e) / r, o = (this.count * this.estimatedModuleSize + n) / r;
        return new Zn(i, s, o, r);
      }
    }
    class Jr {
      constructor(e) {
        this.bottomLeft = e[0], this.topLeft = e[1], this.topRight = e[2];
      }
      getBottomLeft() {
        return this.bottomLeft;
      }
      getTopLeft() {
        return this.topLeft;
      }
      getTopRight() {
        return this.topRight;
      }
    }
    class Ge {
      /**
       * <p>Creates a finder that will search the image for three finder patterns.</p>
       *
       * @param image image to search
       */
      // public constructor(image: BitMatrix) {
      //   this(image, null)
      // }
      constructor(e, t) {
        this.image = e, this.resultPointCallback = t, this.possibleCenters = [], this.crossCheckStateCount = new Int32Array(5), this.resultPointCallback = t;
      }
      getImage() {
        return this.image;
      }
      getPossibleCenters() {
        return this.possibleCenters;
      }
      find(e) {
        const t = e != null && e.get(xe.TRY_HARDER) !== void 0, n = e != null && e.get(xe.PURE_BARCODE) !== void 0, r = this.image, i = r.getHeight(), s = r.getWidth();
        let o = Math.floor(3 * i / (4 * Ge.MAX_MODULES));
        (o < Ge.MIN_SKIP || t) && (o = Ge.MIN_SKIP);
        let a = !1;
        const l = new Int32Array(5);
        for (let f = o - 1; f < i && !a; f += o) {
          l[0] = 0, l[1] = 0, l[2] = 0, l[3] = 0, l[4] = 0;
          let d = 0;
          for (let C = 0; C < s; C++)
            if (r.get(C, f))
              (d & 1) === 1 && d++, l[d]++;
            else if (d & 1)
              l[d]++;
            else if (d === 4)
              if (Ge.foundPatternCross(l)) {
                if (this.handlePossibleCenter(l, f, C, n) === !0)
                  if (o = 2, this.hasSkipped === !0)
                    a = this.haveMultiplyConfirmedCenters();
                  else {
                    const b = this.findRowSkip();
                    b > l[2] && (f += b - l[2] - o, C = s - 1);
                  }
                else {
                  l[0] = l[2], l[1] = l[3], l[2] = l[4], l[3] = 1, l[4] = 0, d = 3;
                  continue;
                }
                d = 0, l[0] = 0, l[1] = 0, l[2] = 0, l[3] = 0, l[4] = 0;
              } else
                l[0] = l[2], l[1] = l[3], l[2] = l[4], l[3] = 1, l[4] = 0, d = 3;
            else
              l[++d]++;
          Ge.foundPatternCross(l) && this.handlePossibleCenter(l, f, s, n) === !0 && (o = l[0], this.hasSkipped && (a = this.haveMultiplyConfirmedCenters()));
        }
        const c = this.selectBestPatterns();
        return W.orderBestPatterns(c), new Jr(c);
      }
      /**
       * Given a count of black/white/black/white/black pixels just seen and an end position,
       * figures the location of the center of this run.
       */
      static centerFromEnd(e, t) {
        return t - e[4] - e[3] - e[2] / 2;
      }
      /**
       * @param stateCount count of black/white/black/white/black pixels just read
       * @return true iff the proportions of the counts is close enough to the 1/1/3/1/1 ratios
       *         used by finder patterns to be considered a match
       */
      static foundPatternCross(e) {
        let t = 0;
        for (let i = 0; i < 5; i++) {
          const s = e[i];
          if (s === 0)
            return !1;
          t += s;
        }
        if (t < 7)
          return !1;
        const n = t / 7, r = n / 2;
        return Math.abs(n - e[0]) < r && Math.abs(n - e[1]) < r && Math.abs(3 * n - e[2]) < 3 * r && Math.abs(n - e[3]) < r && Math.abs(n - e[4]) < r;
      }
      getCrossCheckStateCount() {
        const e = this.crossCheckStateCount;
        return e[0] = 0, e[1] = 0, e[2] = 0, e[3] = 0, e[4] = 0, e;
      }
      /**
       * After a vertical and horizontal scan finds a potential finder pattern, this method
       * "cross-cross-cross-checks" by scanning down diagonally through the center of the possible
       * finder pattern to see if the same proportion is detected.
       *
       * @param startI row where a finder pattern was detected
       * @param centerJ center of the section that appears to cross a finder pattern
       * @param maxCount maximum reasonable number of modules that should be
       *  observed in any reading state, based on the results of the horizontal scan
       * @param originalStateCountTotal The original state count total.
       * @return true if proportions are withing expected limits
       */
      crossCheckDiagonal(e, t, n, r) {
        const i = this.getCrossCheckStateCount();
        let s = 0;
        const o = this.image;
        for (; e >= s && t >= s && o.get(t - s, e - s); )
          i[2]++, s++;
        if (e < s || t < s)
          return !1;
        for (; e >= s && t >= s && !o.get(t - s, e - s) && i[1] <= n; )
          i[1]++, s++;
        if (e < s || t < s || i[1] > n)
          return !1;
        for (; e >= s && t >= s && o.get(t - s, e - s) && i[0] <= n; )
          i[0]++, s++;
        if (i[0] > n)
          return !1;
        const a = o.getHeight(), l = o.getWidth();
        for (s = 1; e + s < a && t + s < l && o.get(t + s, e + s); )
          i[2]++, s++;
        if (e + s >= a || t + s >= l)
          return !1;
        for (; e + s < a && t + s < l && !o.get(t + s, e + s) && i[3] < n; )
          i[3]++, s++;
        if (e + s >= a || t + s >= l || i[3] >= n)
          return !1;
        for (; e + s < a && t + s < l && o.get(t + s, e + s) && i[4] < n; )
          i[4]++, s++;
        if (i[4] >= n)
          return !1;
        const c = i[0] + i[1] + i[2] + i[3] + i[4];
        return Math.abs(c - r) < 2 * r && Ge.foundPatternCross(i);
      }
      /**
       * <p>After a horizontal scan finds a potential finder pattern, this method
       * "cross-checks" by scanning down vertically through the center of the possible
       * finder pattern to see if the same proportion is detected.</p>
       *
       * @param startI row where a finder pattern was detected
       * @param centerJ center of the section that appears to cross a finder pattern
       * @param maxCount maximum reasonable number of modules that should be
       * observed in any reading state, based on the results of the horizontal scan
       * @return vertical center of finder pattern, or {@link Float#NaN} if not found
       */
      crossCheckVertical(e, t, n, r) {
        const i = this.image, s = i.getHeight(), o = this.getCrossCheckStateCount();
        let a = e;
        for (; a >= 0 && i.get(t, a); )
          o[2]++, a--;
        if (a < 0)
          return NaN;
        for (; a >= 0 && !i.get(t, a) && o[1] <= n; )
          o[1]++, a--;
        if (a < 0 || o[1] > n)
          return NaN;
        for (; a >= 0 && i.get(t, a) && o[0] <= n; )
          o[0]++, a--;
        if (o[0] > n)
          return NaN;
        for (a = e + 1; a < s && i.get(t, a); )
          o[2]++, a++;
        if (a === s)
          return NaN;
        for (; a < s && !i.get(t, a) && o[3] < n; )
          o[3]++, a++;
        if (a === s || o[3] >= n)
          return NaN;
        for (; a < s && i.get(t, a) && o[4] < n; )
          o[4]++, a++;
        if (o[4] >= n)
          return NaN;
        const l = o[0] + o[1] + o[2] + o[3] + o[4];
        return 5 * Math.abs(l - r) >= 2 * r ? NaN : Ge.foundPatternCross(o) ? Ge.centerFromEnd(o, a) : NaN;
      }
      /**
       * <p>Like {@link #crossCheckVertical(int, int, int, int)}, and in fact is basically identical,
       * except it reads horizontally instead of vertically. This is used to cross-cross
       * check a vertical cross check and locate the real center of the alignment pattern.</p>
       */
      crossCheckHorizontal(e, t, n, r) {
        const i = this.image, s = i.getWidth(), o = this.getCrossCheckStateCount();
        let a = e;
        for (; a >= 0 && i.get(a, t); )
          o[2]++, a--;
        if (a < 0)
          return NaN;
        for (; a >= 0 && !i.get(a, t) && o[1] <= n; )
          o[1]++, a--;
        if (a < 0 || o[1] > n)
          return NaN;
        for (; a >= 0 && i.get(a, t) && o[0] <= n; )
          o[0]++, a--;
        if (o[0] > n)
          return NaN;
        for (a = e + 1; a < s && i.get(a, t); )
          o[2]++, a++;
        if (a === s)
          return NaN;
        for (; a < s && !i.get(a, t) && o[3] < n; )
          o[3]++, a++;
        if (a === s || o[3] >= n)
          return NaN;
        for (; a < s && i.get(a, t) && o[4] < n; )
          o[4]++, a++;
        if (o[4] >= n)
          return NaN;
        const l = o[0] + o[1] + o[2] + o[3] + o[4];
        return 5 * Math.abs(l - r) >= r ? NaN : Ge.foundPatternCross(o) ? Ge.centerFromEnd(o, a) : NaN;
      }
      /**
       * <p>This is called when a horizontal scan finds a possible alignment pattern. It will
       * cross check with a vertical scan, and if successful, will, ah, cross-cross-check
       * with another horizontal scan. This is needed primarily to locate the real horizontal
       * center of the pattern in cases of extreme skew.
       * And then we cross-cross-cross check with another diagonal scan.</p>
       *
       * <p>If that succeeds the finder pattern location is added to a list that tracks
       * the number of times each location has been nearly-matched as a finder pattern.
       * Each additional find is more evidence that the location is in fact a finder
       * pattern center
       *
       * @param stateCount reading state module counts from horizontal scan
       * @param i row where finder pattern may be found
       * @param j end of possible finder pattern in row
       * @param pureBarcode true if in "pure barcode" mode
       * @return true if a finder pattern candidate was found this time
       */
      handlePossibleCenter(e, t, n, r) {
        const i = e[0] + e[1] + e[2] + e[3] + e[4];
        let s = Ge.centerFromEnd(e, n), o = this.crossCheckVertical(
          t,
          /*(int) */
          Math.floor(s),
          e[2],
          i
        );
        if (!isNaN(o) && (s = this.crossCheckHorizontal(
          /*(int) */
          Math.floor(s),
          /*(int) */
          Math.floor(o),
          e[2],
          i
        ), !isNaN(s) && (!r || this.crossCheckDiagonal(
          /*(int) */
          Math.floor(o),
          /*(int) */
          Math.floor(s),
          e[2],
          i
        )))) {
          const a = i / 7;
          let l = !1;
          const c = this.possibleCenters;
          for (let f = 0, d = c.length; f < d; f++) {
            const C = c[f];
            if (C.aboutEquals(a, o, s)) {
              c[f] = C.combineEstimate(o, s, a), l = !0;
              break;
            }
          }
          if (!l) {
            const f = new Zn(s, o, a);
            c.push(f), this.resultPointCallback !== null && this.resultPointCallback !== void 0 && this.resultPointCallback.foundPossibleResultPoint(f);
          }
          return !0;
        }
        return !1;
      }
      /**
       * @return number of rows we could safely skip during scanning, based on the first
       *         two finder patterns that have been located. In some cases their position will
       *         allow us to infer that the third pattern must lie below a certain point farther
       *         down in the image.
       */
      findRowSkip() {
        if (this.possibleCenters.length <= 1)
          return 0;
        let t = null;
        for (const n of this.possibleCenters)
          if (n.getCount() >= Ge.CENTER_QUORUM)
            if (t == null)
              t = n;
            else
              return this.hasSkipped = !0, /*(int) */
              Math.floor((Math.abs(t.getX() - n.getX()) - Math.abs(t.getY() - n.getY())) / 2);
        return 0;
      }
      /**
       * @return true iff we have found at least 3 finder patterns that have been detected
       *         at least {@link #CENTER_QUORUM} times each, and, the estimated module size of the
       *         candidates is "pretty similar"
       */
      haveMultiplyConfirmedCenters() {
        let e = 0, t = 0;
        const n = this.possibleCenters.length;
        for (const s of this.possibleCenters)
          s.getCount() >= Ge.CENTER_QUORUM && (e++, t += s.getEstimatedModuleSize());
        if (e < 3)
          return !1;
        const r = t / n;
        let i = 0;
        for (const s of this.possibleCenters)
          i += Math.abs(s.getEstimatedModuleSize() - r);
        return i <= 0.05 * t;
      }
      /**
       * @return the 3 best {@link FinderPattern}s from our list of candidates. The "best" are
       *         those that have been detected at least {@link #CENTER_QUORUM} times, and whose module
       *         size differs from the average among those patterns the least
       * @throws NotFoundException if 3 such finder patterns do not exist
       */
      selectBestPatterns() {
        const e = this.possibleCenters.length;
        if (e < 3)
          throw new O();
        const t = this.possibleCenters;
        let n;
        if (e > 3) {
          let r = 0, i = 0;
          for (const a of this.possibleCenters) {
            const l = a.getEstimatedModuleSize();
            r += l, i += l * l;
          }
          n = r / e;
          let s = Math.sqrt(i / e - n * n);
          t.sort(
            /**
             * <p>Orders by furthest from average</p>
             */
            // FurthestFromAverageComparator implements Comparator<FinderPattern>
            (a, l) => {
              const c = Math.abs(l.getEstimatedModuleSize() - n), f = Math.abs(a.getEstimatedModuleSize() - n);
              return c < f ? -1 : c > f ? 1 : 0;
            }
          );
          const o = Math.max(0.2 * n, s);
          for (let a = 0; a < t.length && t.length > 3; a++) {
            const l = t[a];
            Math.abs(l.getEstimatedModuleSize() - n) > o && (t.splice(a, 1), a--);
          }
        }
        if (t.length > 3) {
          let r = 0;
          for (const i of t)
            r += i.getEstimatedModuleSize();
          n = r / t.length, t.sort(
            /**
             * <p>Orders by {@link FinderPattern#getCount()}, descending.</p>
             */
            // CenterComparator implements Comparator<FinderPattern>
            (i, s) => {
              if (s.getCount() === i.getCount()) {
                const o = Math.abs(s.getEstimatedModuleSize() - n), a = Math.abs(i.getEstimatedModuleSize() - n);
                return o < a ? 1 : o > a ? -1 : 0;
              } else
                return s.getCount() - i.getCount();
            }
          ), t.splice(3);
        }
        return [
          t[0],
          t[1],
          t[2]
        ];
      }
    }
    Ge.CENTER_QUORUM = 2, Ge.MIN_SKIP = 3, Ge.MAX_MODULES = 57;
    class an {
      constructor(e) {
        this.image = e;
      }
      getImage() {
        return this.image;
      }
      getResultPointCallback() {
        return this.resultPointCallback;
      }
      /**
       * <p>Detects a QR Code in an image.</p>
       *
       * @return {@link DetectorResult} encapsulating results of detecting a QR Code
       * @throws NotFoundException if QR Code cannot be found
       * @throws FormatException if a QR Code cannot be decoded
       */
      // public detect(): DetectorResult /*throws NotFoundException, FormatException*/ {
      //   return detect(null)
      // }
      /**
       * <p>Detects a QR Code in an image.</p>
       *
       * @param hints optional hints to detector
       * @return {@link DetectorResult} encapsulating results of detecting a QR Code
       * @throws NotFoundException if QR Code cannot be found
       * @throws FormatException if a QR Code cannot be decoded
       */
      detect(e) {
        this.resultPointCallback = e == null ? null : (
          /*(ResultPointCallback) */
          e.get(xe.NEED_RESULT_POINT_CALLBACK)
        );
        const n = new Ge(this.image, this.resultPointCallback).find(e);
        return this.processFinderPatternInfo(n);
      }
      processFinderPatternInfo(e) {
        const t = e.getTopLeft(), n = e.getTopRight(), r = e.getBottomLeft(), i = this.calculateModuleSize(t, n, r);
        if (i < 1)
          throw new O("No pattern found in proccess finder.");
        const s = an.computeDimension(t, n, r, i), o = X.getProvisionalVersionForDimension(s), a = o.getDimensionForVersion() - 7;
        let l = null;
        if (o.getAlignmentPatternCenters().length > 0) {
          const C = n.getX() - t.getX() + r.getX(), p = n.getY() - t.getY() + r.getY(), b = 1 - 3 / a, y = (
            /*(int) */
            Math.floor(t.getX() + b * (C - t.getX()))
          ), _ = (
            /*(int) */
            Math.floor(t.getY() + b * (p - t.getY()))
          );
          for (let P = 4; P <= 16; P <<= 1)
            try {
              l = this.findAlignmentInRegion(i, y, _, P);
              break;
            } catch (F) {
              if (!(F instanceof O))
                throw F;
            }
        }
        const c = an.createTransform(t, n, r, l, s), f = an.sampleGrid(this.image, c, s);
        let d;
        return l === null ? d = [r, t, n] : d = [r, t, n, l], new Cn(f, d);
      }
      static createTransform(e, t, n, r, i) {
        const s = i - 3.5;
        let o, a, l, c;
        return r !== null ? (o = r.getX(), a = r.getY(), l = s - 3, c = l) : (o = t.getX() - e.getX() + n.getX(), a = t.getY() - e.getY() + n.getY(), l = s, c = s), ut.quadrilateralToQuadrilateral(3.5, 3.5, s, 3.5, l, c, 3.5, s, e.getX(), e.getY(), t.getX(), t.getY(), o, a, n.getX(), n.getY());
      }
      static sampleGrid(e, t, n) {
        return _t.getInstance().sampleGridWithTransform(e, n, n, t);
      }
      /**
       * <p>Computes the dimension (number of modules on a size) of the QR Code based on the position
       * of the finder patterns and estimated module size.</p>
       */
      static computeDimension(e, t, n, r) {
        const i = oe.round(W.distance(e, t) / r), s = oe.round(W.distance(e, n) / r);
        let o = Math.floor((i + s) / 2) + 7;
        switch (o & 3) {
          case 0:
            o++;
            break;
          case 2:
            o--;
            break;
          case 3:
            throw new O("Dimensions could be not found.");
        }
        return o;
      }
      /**
       * <p>Computes an average estimated module size based on estimated derived from the positions
       * of the three finder patterns.</p>
       *
       * @param topLeft detected top-left finder pattern center
       * @param topRight detected top-right finder pattern center
       * @param bottomLeft detected bottom-left finder pattern center
       * @return estimated module size
       */
      calculateModuleSize(e, t, n) {
        return (this.calculateModuleSizeOneWay(e, t) + this.calculateModuleSizeOneWay(e, n)) / 2;
      }
      /**
       * <p>Estimates module size based on two finder patterns -- it uses
       * {@link #sizeOfBlackWhiteBlackRunBothWays(int, int, int, int)} to figure the
       * width of each, measuring along the axis between their centers.</p>
       */
      calculateModuleSizeOneWay(e, t) {
        const n = this.sizeOfBlackWhiteBlackRunBothWays(
          /*(int) */
          Math.floor(e.getX()),
          /*(int) */
          Math.floor(e.getY()),
          /*(int) */
          Math.floor(t.getX()),
          /*(int) */
          Math.floor(t.getY())
        ), r = this.sizeOfBlackWhiteBlackRunBothWays(
          /*(int) */
          Math.floor(t.getX()),
          /*(int) */
          Math.floor(t.getY()),
          /*(int) */
          Math.floor(e.getX()),
          /*(int) */
          Math.floor(e.getY())
        );
        return isNaN(n) ? r / 7 : isNaN(r) ? n / 7 : (n + r) / 14;
      }
      /**
       * See {@link #sizeOfBlackWhiteBlackRun(int, int, int, int)}; computes the total width of
       * a finder pattern by looking for a black-white-black run from the center in the direction
       * of another point (another finder pattern center), and in the opposite direction too.
       */
      sizeOfBlackWhiteBlackRunBothWays(e, t, n, r) {
        let i = this.sizeOfBlackWhiteBlackRun(e, t, n, r), s = 1, o = e - (n - e);
        o < 0 ? (s = e / /*(float) */
        (e - o), o = 0) : o >= this.image.getWidth() && (s = (this.image.getWidth() - 1 - e) / /*(float) */
        (o - e), o = this.image.getWidth() - 1);
        let a = (
          /*(int) */
          Math.floor(t - (r - t) * s)
        );
        return s = 1, a < 0 ? (s = t / /*(float) */
        (t - a), a = 0) : a >= this.image.getHeight() && (s = (this.image.getHeight() - 1 - t) / /*(float) */
        (a - t), a = this.image.getHeight() - 1), o = /*(int) */
        Math.floor(e + (o - e) * s), i += this.sizeOfBlackWhiteBlackRun(e, t, o, a), i - 1;
      }
      /**
       * <p>This method traces a line from a point in the image, in the direction towards another point.
       * It begins in a black region, and keeps going until it finds white, then black, then white again.
       * It reports the distance from the start to this point.</p>
       *
       * <p>This is used when figuring out how wide a finder pattern is, when the finder pattern
       * may be skewed or rotated.</p>
       */
      sizeOfBlackWhiteBlackRun(e, t, n, r) {
        const i = Math.abs(r - t) > Math.abs(n - e);
        if (i) {
          let C = e;
          e = t, t = C, C = n, n = r, r = C;
        }
        const s = Math.abs(n - e), o = Math.abs(r - t);
        let a = -s / 2;
        const l = e < n ? 1 : -1, c = t < r ? 1 : -1;
        let f = 0;
        const d = n + l;
        for (let C = e, p = t; C !== d; C += l) {
          const b = i ? p : C, y = i ? C : p;
          if (f === 1 === this.image.get(b, y)) {
            if (f === 2)
              return oe.distance(C, p, e, t);
            f++;
          }
          if (a += o, a > 0) {
            if (p === r)
              break;
            p += c, a -= s;
          }
        }
        return f === 2 ? oe.distance(n + l, r, e, t) : NaN;
      }
      /**
       * <p>Attempts to locate an alignment pattern in a limited region of the image, which is
       * guessed to contain it. This method uses {@link AlignmentPattern}.</p>
       *
       * @param overallEstModuleSize estimated module size so far
       * @param estAlignmentX x coordinate of center of area probably containing alignment pattern
       * @param estAlignmentY y coordinate of above
       * @param allowanceFactor number of pixels in all directions to search from the center
       * @return {@link AlignmentPattern} if found, or null otherwise
       * @throws NotFoundException if an unexpected error occurs during detection
       */
      findAlignmentInRegion(e, t, n, r) {
        const i = (
          /*(int) */
          Math.floor(r * e)
        ), s = Math.max(0, t - i), o = Math.min(this.image.getWidth() - 1, t + i);
        if (o - s < e * 3)
          throw new O("Alignment top exceeds estimated module size.");
        const a = Math.max(0, n - i), l = Math.min(this.image.getHeight() - 1, n + i);
        if (l - a < e * 3)
          throw new O("Alignment bottom exceeds estimated module size.");
        return new yn(this.image, s, a, o - s, l - a, e, this.resultPointCallback).find();
      }
    }
    class Mt {
      constructor() {
        this.decoder = new Qr();
      }
      getDecoder() {
        return this.decoder;
      }
      /**
       * Locates and decodes a QR code in an image.
       *
       * @return a representing: string the content encoded by the QR code
       * @throws NotFoundException if a QR code cannot be found
       * @throws FormatException if a QR code cannot be decoded
       * @throws ChecksumException if error correction fails
       */
      /*@Override*/
      // public decode(image: BinaryBitmap): Result /*throws NotFoundException, ChecksumException, FormatException */ {
      //   return this.decode(image, null)
      // }
      /*@Override*/
      decode(e, t) {
        let n, r;
        if (t != null && t.get(xe.PURE_BARCODE) !== void 0) {
          const a = Mt.extractPureBits(e.getBlackMatrix());
          n = this.decoder.decodeBitMatrix(a, t), r = Mt.NO_POINTS;
        } else {
          const a = new an(e.getBlackMatrix()).detect(t);
          n = this.decoder.decodeBitMatrix(a.getBits(), t), r = a.getPoints();
        }
        n.getOther() instanceof dr && n.getOther().applyMirroredCorrection(r);
        const i = new je(n.getText(), n.getRawBytes(), void 0, r, z.QR_CODE, void 0), s = n.getByteSegments();
        s !== null && i.putMetadata(Ue.BYTE_SEGMENTS, s);
        const o = n.getECLevel();
        return o !== null && i.putMetadata(Ue.ERROR_CORRECTION_LEVEL, o), n.hasStructuredAppend() && (i.putMetadata(Ue.STRUCTURED_APPEND_SEQUENCE, n.getStructuredAppendSequenceNumber()), i.putMetadata(Ue.STRUCTURED_APPEND_PARITY, n.getStructuredAppendParity())), i;
      }
      /*@Override*/
      reset() {
      }
      /**
       * This method detects a code in a "pure" image -- that is, pure monochrome image
       * which contains only an unrotated, unskewed, image of a code, with some white border
       * around it. This is a specialized method that works exceptionally fast in this special
       * case.
       *
       * @see com.google.zxing.datamatrix.DataMatrixReader#extractPureBits(BitMatrix)
       */
      static extractPureBits(e) {
        const t = e.getTopLeftOnBit(), n = e.getBottomRightOnBit();
        if (t === null || n === null)
          throw new O();
        const r = this.moduleSize(t, e);
        let i = t[1], s = n[1], o = t[0], a = n[0];
        if (o >= a || i >= s)
          throw new O();
        if (s - i !== a - o && (a = o + (s - i), a >= e.getWidth()))
          throw new O();
        const l = Math.round((a - o + 1) / r), c = Math.round((s - i + 1) / r);
        if (l <= 0 || c <= 0)
          throw new O();
        if (c !== l)
          throw new O();
        const f = (
          /*(int) */
          Math.floor(r / 2)
        );
        i += f, o += f;
        const d = o + /*(int) */
        Math.floor((l - 1) * r) - a;
        if (d > 0) {
          if (d > f)
            throw new O();
          o -= d;
        }
        const C = i + /*(int) */
        Math.floor((c - 1) * r) - s;
        if (C > 0) {
          if (C > f)
            throw new O();
          i -= C;
        }
        const p = new Fe(l, c);
        for (let b = 0; b < c; b++) {
          const y = i + /*(int) */
          Math.floor(b * r);
          for (let _ = 0; _ < l; _++)
            e.get(o + /*(int) */
            Math.floor(_ * r), y) && p.set(_, b);
        }
        return p;
      }
      static moduleSize(e, t) {
        const n = t.getHeight(), r = t.getWidth();
        let i = e[0], s = e[1], o = !0, a = 0;
        for (; i < r && s < n; ) {
          if (o !== t.get(i, s)) {
            if (++a === 5)
              break;
            o = !o;
          }
          i++, s++;
        }
        if (i === r || s === n)
          throw new O();
        return (i - e[0]) / 7;
      }
    }
    Mt.NO_POINTS = new Array();
    class K {
      PDF417Common() {
      }
      /**
       * @param moduleBitCount values to sum
       * @return sum of values
       * @deprecated call {@link MathUtils#sum(int[])}
       */
      // @Deprecated
      static getBitCountSum(e) {
        return oe.sum(e);
      }
      static toIntArray(e) {
        if (e == null || !e.length)
          return K.EMPTY_INT_ARRAY;
        const t = new Int32Array(e.length);
        let n = 0;
        for (const r of e)
          t[n++] = r;
        return t;
      }
      /**
       * @param symbol encoded symbol to translate to a codeword
       * @return the codeword corresponding to the symbol.
       */
      static getCodeword(e) {
        const t = me.binarySearch(K.SYMBOL_TABLE, e & 262143);
        return t < 0 ? -1 : (K.CODEWORD_TABLE[t] - 1) % K.NUMBER_OF_CODEWORDS;
      }
    }
    K.NUMBER_OF_CODEWORDS = 929, K.MAX_CODEWORDS_IN_BARCODE = K.NUMBER_OF_CODEWORDS - 1, K.MIN_ROWS_IN_BARCODE = 3, K.MAX_ROWS_IN_BARCODE = 90, K.MODULES_IN_CODEWORD = 17, K.MODULES_IN_STOP_PATTERN = 18, K.BARS_IN_MODULE = 8, K.EMPTY_INT_ARRAY = new Int32Array([]), K.SYMBOL_TABLE = Int32Array.from([
      66142,
      66170,
      66206,
      66236,
      66290,
      66292,
      66350,
      66382,
      66396,
      66454,
      66470,
      66476,
      66594,
      66600,
      66614,
      66626,
      66628,
      66632,
      66640,
      66654,
      66662,
      66668,
      66682,
      66690,
      66718,
      66720,
      66748,
      66758,
      66776,
      66798,
      66802,
      66804,
      66820,
      66824,
      66832,
      66846,
      66848,
      66876,
      66880,
      66936,
      66950,
      66956,
      66968,
      66992,
      67006,
      67022,
      67036,
      67042,
      67044,
      67048,
      67062,
      67118,
      67150,
      67164,
      67214,
      67228,
      67256,
      67294,
      67322,
      67350,
      67366,
      67372,
      67398,
      67404,
      67416,
      67438,
      67474,
      67476,
      67490,
      67492,
      67496,
      67510,
      67618,
      67624,
      67650,
      67656,
      67664,
      67678,
      67686,
      67692,
      67706,
      67714,
      67716,
      67728,
      67742,
      67744,
      67772,
      67782,
      67788,
      67800,
      67822,
      67826,
      67828,
      67842,
      67848,
      67870,
      67872,
      67900,
      67904,
      67960,
      67974,
      67992,
      68016,
      68030,
      68046,
      68060,
      68066,
      68068,
      68072,
      68086,
      68104,
      68112,
      68126,
      68128,
      68156,
      68160,
      68216,
      68336,
      68358,
      68364,
      68376,
      68400,
      68414,
      68448,
      68476,
      68494,
      68508,
      68536,
      68546,
      68548,
      68552,
      68560,
      68574,
      68582,
      68588,
      68654,
      68686,
      68700,
      68706,
      68708,
      68712,
      68726,
      68750,
      68764,
      68792,
      68802,
      68804,
      68808,
      68816,
      68830,
      68838,
      68844,
      68858,
      68878,
      68892,
      68920,
      68976,
      68990,
      68994,
      68996,
      69e3,
      69008,
      69022,
      69024,
      69052,
      69062,
      69068,
      69080,
      69102,
      69106,
      69108,
      69142,
      69158,
      69164,
      69190,
      69208,
      69230,
      69254,
      69260,
      69272,
      69296,
      69310,
      69326,
      69340,
      69386,
      69394,
      69396,
      69410,
      69416,
      69430,
      69442,
      69444,
      69448,
      69456,
      69470,
      69478,
      69484,
      69554,
      69556,
      69666,
      69672,
      69698,
      69704,
      69712,
      69726,
      69754,
      69762,
      69764,
      69776,
      69790,
      69792,
      69820,
      69830,
      69836,
      69848,
      69870,
      69874,
      69876,
      69890,
      69918,
      69920,
      69948,
      69952,
      70008,
      70022,
      70040,
      70064,
      70078,
      70094,
      70108,
      70114,
      70116,
      70120,
      70134,
      70152,
      70174,
      70176,
      70264,
      70384,
      70412,
      70448,
      70462,
      70496,
      70524,
      70542,
      70556,
      70584,
      70594,
      70600,
      70608,
      70622,
      70630,
      70636,
      70664,
      70672,
      70686,
      70688,
      70716,
      70720,
      70776,
      70896,
      71136,
      71180,
      71192,
      71216,
      71230,
      71264,
      71292,
      71360,
      71416,
      71452,
      71480,
      71536,
      71550,
      71554,
      71556,
      71560,
      71568,
      71582,
      71584,
      71612,
      71622,
      71628,
      71640,
      71662,
      71726,
      71732,
      71758,
      71772,
      71778,
      71780,
      71784,
      71798,
      71822,
      71836,
      71864,
      71874,
      71880,
      71888,
      71902,
      71910,
      71916,
      71930,
      71950,
      71964,
      71992,
      72048,
      72062,
      72066,
      72068,
      72080,
      72094,
      72096,
      72124,
      72134,
      72140,
      72152,
      72174,
      72178,
      72180,
      72206,
      72220,
      72248,
      72304,
      72318,
      72416,
      72444,
      72456,
      72464,
      72478,
      72480,
      72508,
      72512,
      72568,
      72588,
      72600,
      72624,
      72638,
      72654,
      72668,
      72674,
      72676,
      72680,
      72694,
      72726,
      72742,
      72748,
      72774,
      72780,
      72792,
      72814,
      72838,
      72856,
      72880,
      72894,
      72910,
      72924,
      72930,
      72932,
      72936,
      72950,
      72966,
      72972,
      72984,
      73008,
      73022,
      73056,
      73084,
      73102,
      73116,
      73144,
      73156,
      73160,
      73168,
      73182,
      73190,
      73196,
      73210,
      73226,
      73234,
      73236,
      73250,
      73252,
      73256,
      73270,
      73282,
      73284,
      73296,
      73310,
      73318,
      73324,
      73346,
      73348,
      73352,
      73360,
      73374,
      73376,
      73404,
      73414,
      73420,
      73432,
      73454,
      73498,
      73518,
      73522,
      73524,
      73550,
      73564,
      73570,
      73572,
      73576,
      73590,
      73800,
      73822,
      73858,
      73860,
      73872,
      73886,
      73888,
      73916,
      73944,
      73970,
      73972,
      73992,
      74014,
      74016,
      74044,
      74048,
      74104,
      74118,
      74136,
      74160,
      74174,
      74210,
      74212,
      74216,
      74230,
      74244,
      74256,
      74270,
      74272,
      74360,
      74480,
      74502,
      74508,
      74544,
      74558,
      74592,
      74620,
      74638,
      74652,
      74680,
      74690,
      74696,
      74704,
      74726,
      74732,
      74782,
      74784,
      74812,
      74992,
      75232,
      75288,
      75326,
      75360,
      75388,
      75456,
      75512,
      75576,
      75632,
      75646,
      75650,
      75652,
      75664,
      75678,
      75680,
      75708,
      75718,
      75724,
      75736,
      75758,
      75808,
      75836,
      75840,
      75896,
      76016,
      76256,
      76736,
      76824,
      76848,
      76862,
      76896,
      76924,
      76992,
      77048,
      77296,
      77340,
      77368,
      77424,
      77438,
      77536,
      77564,
      77572,
      77576,
      77584,
      77600,
      77628,
      77632,
      77688,
      77702,
      77708,
      77720,
      77744,
      77758,
      77774,
      77788,
      77870,
      77902,
      77916,
      77922,
      77928,
      77966,
      77980,
      78008,
      78018,
      78024,
      78032,
      78046,
      78060,
      78074,
      78094,
      78136,
      78192,
      78206,
      78210,
      78212,
      78224,
      78238,
      78240,
      78268,
      78278,
      78284,
      78296,
      78322,
      78324,
      78350,
      78364,
      78448,
      78462,
      78560,
      78588,
      78600,
      78622,
      78624,
      78652,
      78656,
      78712,
      78726,
      78744,
      78768,
      78782,
      78798,
      78812,
      78818,
      78820,
      78824,
      78838,
      78862,
      78876,
      78904,
      78960,
      78974,
      79072,
      79100,
      79296,
      79352,
      79368,
      79376,
      79390,
      79392,
      79420,
      79424,
      79480,
      79600,
      79628,
      79640,
      79664,
      79678,
      79712,
      79740,
      79772,
      79800,
      79810,
      79812,
      79816,
      79824,
      79838,
      79846,
      79852,
      79894,
      79910,
      79916,
      79942,
      79948,
      79960,
      79982,
      79988,
      80006,
      80024,
      80048,
      80062,
      80078,
      80092,
      80098,
      80100,
      80104,
      80134,
      80140,
      80176,
      80190,
      80224,
      80252,
      80270,
      80284,
      80312,
      80328,
      80336,
      80350,
      80358,
      80364,
      80378,
      80390,
      80396,
      80408,
      80432,
      80446,
      80480,
      80508,
      80576,
      80632,
      80654,
      80668,
      80696,
      80752,
      80766,
      80776,
      80784,
      80798,
      80800,
      80828,
      80844,
      80856,
      80878,
      80882,
      80884,
      80914,
      80916,
      80930,
      80932,
      80936,
      80950,
      80962,
      80968,
      80976,
      80990,
      80998,
      81004,
      81026,
      81028,
      81040,
      81054,
      81056,
      81084,
      81094,
      81100,
      81112,
      81134,
      81154,
      81156,
      81160,
      81168,
      81182,
      81184,
      81212,
      81216,
      81272,
      81286,
      81292,
      81304,
      81328,
      81342,
      81358,
      81372,
      81380,
      81384,
      81398,
      81434,
      81454,
      81458,
      81460,
      81486,
      81500,
      81506,
      81508,
      81512,
      81526,
      81550,
      81564,
      81592,
      81602,
      81604,
      81608,
      81616,
      81630,
      81638,
      81644,
      81702,
      81708,
      81722,
      81734,
      81740,
      81752,
      81774,
      81778,
      81780,
      82050,
      82078,
      82080,
      82108,
      82180,
      82184,
      82192,
      82206,
      82208,
      82236,
      82240,
      82296,
      82316,
      82328,
      82352,
      82366,
      82402,
      82404,
      82408,
      82440,
      82448,
      82462,
      82464,
      82492,
      82496,
      82552,
      82672,
      82694,
      82700,
      82712,
      82736,
      82750,
      82784,
      82812,
      82830,
      82882,
      82884,
      82888,
      82896,
      82918,
      82924,
      82952,
      82960,
      82974,
      82976,
      83004,
      83008,
      83064,
      83184,
      83424,
      83468,
      83480,
      83504,
      83518,
      83552,
      83580,
      83648,
      83704,
      83740,
      83768,
      83824,
      83838,
      83842,
      83844,
      83848,
      83856,
      83872,
      83900,
      83910,
      83916,
      83928,
      83950,
      83984,
      84e3,
      84028,
      84032,
      84088,
      84208,
      84448,
      84928,
      85040,
      85054,
      85088,
      85116,
      85184,
      85240,
      85488,
      85560,
      85616,
      85630,
      85728,
      85756,
      85764,
      85768,
      85776,
      85790,
      85792,
      85820,
      85824,
      85880,
      85894,
      85900,
      85912,
      85936,
      85966,
      85980,
      86048,
      86080,
      86136,
      86256,
      86496,
      86976,
      88160,
      88188,
      88256,
      88312,
      88560,
      89056,
      89200,
      89214,
      89312,
      89340,
      89536,
      89592,
      89608,
      89616,
      89632,
      89664,
      89720,
      89840,
      89868,
      89880,
      89904,
      89952,
      89980,
      89998,
      90012,
      90040,
      90190,
      90204,
      90254,
      90268,
      90296,
      90306,
      90308,
      90312,
      90334,
      90382,
      90396,
      90424,
      90480,
      90494,
      90500,
      90504,
      90512,
      90526,
      90528,
      90556,
      90566,
      90572,
      90584,
      90610,
      90612,
      90638,
      90652,
      90680,
      90736,
      90750,
      90848,
      90876,
      90884,
      90888,
      90896,
      90910,
      90912,
      90940,
      90944,
      91e3,
      91014,
      91020,
      91032,
      91056,
      91070,
      91086,
      91100,
      91106,
      91108,
      91112,
      91126,
      91150,
      91164,
      91192,
      91248,
      91262,
      91360,
      91388,
      91584,
      91640,
      91664,
      91678,
      91680,
      91708,
      91712,
      91768,
      91888,
      91928,
      91952,
      91966,
      92e3,
      92028,
      92046,
      92060,
      92088,
      92098,
      92100,
      92104,
      92112,
      92126,
      92134,
      92140,
      92188,
      92216,
      92272,
      92384,
      92412,
      92608,
      92664,
      93168,
      93200,
      93214,
      93216,
      93244,
      93248,
      93304,
      93424,
      93664,
      93720,
      93744,
      93758,
      93792,
      93820,
      93888,
      93944,
      93980,
      94008,
      94064,
      94078,
      94084,
      94088,
      94096,
      94110,
      94112,
      94140,
      94150,
      94156,
      94168,
      94246,
      94252,
      94278,
      94284,
      94296,
      94318,
      94342,
      94348,
      94360,
      94384,
      94398,
      94414,
      94428,
      94440,
      94470,
      94476,
      94488,
      94512,
      94526,
      94560,
      94588,
      94606,
      94620,
      94648,
      94658,
      94660,
      94664,
      94672,
      94686,
      94694,
      94700,
      94714,
      94726,
      94732,
      94744,
      94768,
      94782,
      94816,
      94844,
      94912,
      94968,
      94990,
      95004,
      95032,
      95088,
      95102,
      95112,
      95120,
      95134,
      95136,
      95164,
      95180,
      95192,
      95214,
      95218,
      95220,
      95244,
      95256,
      95280,
      95294,
      95328,
      95356,
      95424,
      95480,
      95728,
      95758,
      95772,
      95800,
      95856,
      95870,
      95968,
      95996,
      96008,
      96016,
      96030,
      96032,
      96060,
      96064,
      96120,
      96152,
      96176,
      96190,
      96220,
      96226,
      96228,
      96232,
      96290,
      96292,
      96296,
      96310,
      96322,
      96324,
      96328,
      96336,
      96350,
      96358,
      96364,
      96386,
      96388,
      96392,
      96400,
      96414,
      96416,
      96444,
      96454,
      96460,
      96472,
      96494,
      96498,
      96500,
      96514,
      96516,
      96520,
      96528,
      96542,
      96544,
      96572,
      96576,
      96632,
      96646,
      96652,
      96664,
      96688,
      96702,
      96718,
      96732,
      96738,
      96740,
      96744,
      96758,
      96772,
      96776,
      96784,
      96798,
      96800,
      96828,
      96832,
      96888,
      97008,
      97030,
      97036,
      97048,
      97072,
      97086,
      97120,
      97148,
      97166,
      97180,
      97208,
      97220,
      97224,
      97232,
      97246,
      97254,
      97260,
      97326,
      97330,
      97332,
      97358,
      97372,
      97378,
      97380,
      97384,
      97398,
      97422,
      97436,
      97464,
      97474,
      97476,
      97480,
      97488,
      97502,
      97510,
      97516,
      97550,
      97564,
      97592,
      97648,
      97666,
      97668,
      97672,
      97680,
      97694,
      97696,
      97724,
      97734,
      97740,
      97752,
      97774,
      97830,
      97836,
      97850,
      97862,
      97868,
      97880,
      97902,
      97906,
      97908,
      97926,
      97932,
      97944,
      97968,
      97998,
      98012,
      98018,
      98020,
      98024,
      98038,
      98618,
      98674,
      98676,
      98838,
      98854,
      98874,
      98892,
      98904,
      98926,
      98930,
      98932,
      98968,
      99006,
      99042,
      99044,
      99048,
      99062,
      99166,
      99194,
      99246,
      99286,
      99350,
      99366,
      99372,
      99386,
      99398,
      99416,
      99438,
      99442,
      99444,
      99462,
      99504,
      99518,
      99534,
      99548,
      99554,
      99556,
      99560,
      99574,
      99590,
      99596,
      99608,
      99632,
      99646,
      99680,
      99708,
      99726,
      99740,
      99768,
      99778,
      99780,
      99784,
      99792,
      99806,
      99814,
      99820,
      99834,
      99858,
      99860,
      99874,
      99880,
      99894,
      99906,
      99920,
      99934,
      99962,
      99970,
      99972,
      99976,
      99984,
      99998,
      1e5,
      100028,
      100038,
      100044,
      100056,
      100078,
      100082,
      100084,
      100142,
      100174,
      100188,
      100246,
      100262,
      100268,
      100306,
      100308,
      100390,
      100396,
      100410,
      100422,
      100428,
      100440,
      100462,
      100466,
      100468,
      100486,
      100504,
      100528,
      100542,
      100558,
      100572,
      100578,
      100580,
      100584,
      100598,
      100620,
      100656,
      100670,
      100704,
      100732,
      100750,
      100792,
      100802,
      100808,
      100816,
      100830,
      100838,
      100844,
      100858,
      100888,
      100912,
      100926,
      100960,
      100988,
      101056,
      101112,
      101148,
      101176,
      101232,
      101246,
      101250,
      101252,
      101256,
      101264,
      101278,
      101280,
      101308,
      101318,
      101324,
      101336,
      101358,
      101362,
      101364,
      101410,
      101412,
      101416,
      101430,
      101442,
      101448,
      101456,
      101470,
      101478,
      101498,
      101506,
      101508,
      101520,
      101534,
      101536,
      101564,
      101580,
      101618,
      101620,
      101636,
      101640,
      101648,
      101662,
      101664,
      101692,
      101696,
      101752,
      101766,
      101784,
      101838,
      101858,
      101860,
      101864,
      101934,
      101938,
      101940,
      101966,
      101980,
      101986,
      101988,
      101992,
      102030,
      102044,
      102072,
      102082,
      102084,
      102088,
      102096,
      102138,
      102166,
      102182,
      102188,
      102214,
      102220,
      102232,
      102254,
      102282,
      102290,
      102292,
      102306,
      102308,
      102312,
      102326,
      102444,
      102458,
      102470,
      102476,
      102488,
      102514,
      102516,
      102534,
      102552,
      102576,
      102590,
      102606,
      102620,
      102626,
      102632,
      102646,
      102662,
      102668,
      102704,
      102718,
      102752,
      102780,
      102798,
      102812,
      102840,
      102850,
      102856,
      102864,
      102878,
      102886,
      102892,
      102906,
      102936,
      102974,
      103008,
      103036,
      103104,
      103160,
      103224,
      103280,
      103294,
      103298,
      103300,
      103312,
      103326,
      103328,
      103356,
      103366,
      103372,
      103384,
      103406,
      103410,
      103412,
      103472,
      103486,
      103520,
      103548,
      103616,
      103672,
      103920,
      103992,
      104048,
      104062,
      104160,
      104188,
      104194,
      104196,
      104200,
      104208,
      104224,
      104252,
      104256,
      104312,
      104326,
      104332,
      104344,
      104368,
      104382,
      104398,
      104412,
      104418,
      104420,
      104424,
      104482,
      104484,
      104514,
      104520,
      104528,
      104542,
      104550,
      104570,
      104578,
      104580,
      104592,
      104606,
      104608,
      104636,
      104652,
      104690,
      104692,
      104706,
      104712,
      104734,
      104736,
      104764,
      104768,
      104824,
      104838,
      104856,
      104910,
      104930,
      104932,
      104936,
      104968,
      104976,
      104990,
      104992,
      105020,
      105024,
      105080,
      105200,
      105240,
      105278,
      105312,
      105372,
      105410,
      105412,
      105416,
      105424,
      105446,
      105518,
      105524,
      105550,
      105564,
      105570,
      105572,
      105576,
      105614,
      105628,
      105656,
      105666,
      105672,
      105680,
      105702,
      105722,
      105742,
      105756,
      105784,
      105840,
      105854,
      105858,
      105860,
      105864,
      105872,
      105888,
      105932,
      105970,
      105972,
      106006,
      106022,
      106028,
      106054,
      106060,
      106072,
      106100,
      106118,
      106124,
      106136,
      106160,
      106174,
      106190,
      106210,
      106212,
      106216,
      106250,
      106258,
      106260,
      106274,
      106276,
      106280,
      106306,
      106308,
      106312,
      106320,
      106334,
      106348,
      106394,
      106414,
      106418,
      106420,
      106566,
      106572,
      106610,
      106612,
      106630,
      106636,
      106648,
      106672,
      106686,
      106722,
      106724,
      106728,
      106742,
      106758,
      106764,
      106776,
      106800,
      106814,
      106848,
      106876,
      106894,
      106908,
      106936,
      106946,
      106948,
      106952,
      106960,
      106974,
      106982,
      106988,
      107032,
      107056,
      107070,
      107104,
      107132,
      107200,
      107256,
      107292,
      107320,
      107376,
      107390,
      107394,
      107396,
      107400,
      107408,
      107422,
      107424,
      107452,
      107462,
      107468,
      107480,
      107502,
      107506,
      107508,
      107544,
      107568,
      107582,
      107616,
      107644,
      107712,
      107768,
      108016,
      108060,
      108088,
      108144,
      108158,
      108256,
      108284,
      108290,
      108292,
      108296,
      108304,
      108318,
      108320,
      108348,
      108352,
      108408,
      108422,
      108428,
      108440,
      108464,
      108478,
      108494,
      108508,
      108514,
      108516,
      108520,
      108592,
      108640,
      108668,
      108736,
      108792,
      109040,
      109536,
      109680,
      109694,
      109792,
      109820,
      110016,
      110072,
      110084,
      110088,
      110096,
      110112,
      110140,
      110144,
      110200,
      110320,
      110342,
      110348,
      110360,
      110384,
      110398,
      110432,
      110460,
      110478,
      110492,
      110520,
      110532,
      110536,
      110544,
      110558,
      110658,
      110686,
      110714,
      110722,
      110724,
      110728,
      110736,
      110750,
      110752,
      110780,
      110796,
      110834,
      110836,
      110850,
      110852,
      110856,
      110864,
      110878,
      110880,
      110908,
      110912,
      110968,
      110982,
      111e3,
      111054,
      111074,
      111076,
      111080,
      111108,
      111112,
      111120,
      111134,
      111136,
      111164,
      111168,
      111224,
      111344,
      111372,
      111422,
      111456,
      111516,
      111554,
      111556,
      111560,
      111568,
      111590,
      111632,
      111646,
      111648,
      111676,
      111680,
      111736,
      111856,
      112096,
      112152,
      112224,
      112252,
      112320,
      112440,
      112514,
      112516,
      112520,
      112528,
      112542,
      112544,
      112588,
      112686,
      112718,
      112732,
      112782,
      112796,
      112824,
      112834,
      112836,
      112840,
      112848,
      112870,
      112890,
      112910,
      112924,
      112952,
      113008,
      113022,
      113026,
      113028,
      113032,
      113040,
      113054,
      113056,
      113100,
      113138,
      113140,
      113166,
      113180,
      113208,
      113264,
      113278,
      113376,
      113404,
      113416,
      113424,
      113440,
      113468,
      113472,
      113560,
      113614,
      113634,
      113636,
      113640,
      113686,
      113702,
      113708,
      113734,
      113740,
      113752,
      113778,
      113780,
      113798,
      113804,
      113816,
      113840,
      113854,
      113870,
      113890,
      113892,
      113896,
      113926,
      113932,
      113944,
      113968,
      113982,
      114016,
      114044,
      114076,
      114114,
      114116,
      114120,
      114128,
      114150,
      114170,
      114194,
      114196,
      114210,
      114212,
      114216,
      114242,
      114244,
      114248,
      114256,
      114270,
      114278,
      114306,
      114308,
      114312,
      114320,
      114334,
      114336,
      114364,
      114380,
      114420,
      114458,
      114478,
      114482,
      114484,
      114510,
      114524,
      114530,
      114532,
      114536,
      114842,
      114866,
      114868,
      114970,
      114994,
      114996,
      115042,
      115044,
      115048,
      115062,
      115130,
      115226,
      115250,
      115252,
      115278,
      115292,
      115298,
      115300,
      115304,
      115318,
      115342,
      115394,
      115396,
      115400,
      115408,
      115422,
      115430,
      115436,
      115450,
      115478,
      115494,
      115514,
      115526,
      115532,
      115570,
      115572,
      115738,
      115758,
      115762,
      115764,
      115790,
      115804,
      115810,
      115812,
      115816,
      115830,
      115854,
      115868,
      115896,
      115906,
      115912,
      115920,
      115934,
      115942,
      115948,
      115962,
      115996,
      116024,
      116080,
      116094,
      116098,
      116100,
      116104,
      116112,
      116126,
      116128,
      116156,
      116166,
      116172,
      116184,
      116206,
      116210,
      116212,
      116246,
      116262,
      116268,
      116282,
      116294,
      116300,
      116312,
      116334,
      116338,
      116340,
      116358,
      116364,
      116376,
      116400,
      116414,
      116430,
      116444,
      116450,
      116452,
      116456,
      116498,
      116500,
      116514,
      116520,
      116534,
      116546,
      116548,
      116552,
      116560,
      116574,
      116582,
      116588,
      116602,
      116654,
      116694,
      116714,
      116762,
      116782,
      116786,
      116788,
      116814,
      116828,
      116834,
      116836,
      116840,
      116854,
      116878,
      116892,
      116920,
      116930,
      116936,
      116944,
      116958,
      116966,
      116972,
      116986,
      117006,
      117048,
      117104,
      117118,
      117122,
      117124,
      117136,
      117150,
      117152,
      117180,
      117190,
      117196,
      117208,
      117230,
      117234,
      117236,
      117304,
      117360,
      117374,
      117472,
      117500,
      117506,
      117508,
      117512,
      117520,
      117536,
      117564,
      117568,
      117624,
      117638,
      117644,
      117656,
      117680,
      117694,
      117710,
      117724,
      117730,
      117732,
      117736,
      117750,
      117782,
      117798,
      117804,
      117818,
      117830,
      117848,
      117874,
      117876,
      117894,
      117936,
      117950,
      117966,
      117986,
      117988,
      117992,
      118022,
      118028,
      118040,
      118064,
      118078,
      118112,
      118140,
      118172,
      118210,
      118212,
      118216,
      118224,
      118238,
      118246,
      118266,
      118306,
      118312,
      118338,
      118352,
      118366,
      118374,
      118394,
      118402,
      118404,
      118408,
      118416,
      118430,
      118432,
      118460,
      118476,
      118514,
      118516,
      118574,
      118578,
      118580,
      118606,
      118620,
      118626,
      118628,
      118632,
      118678,
      118694,
      118700,
      118730,
      118738,
      118740,
      118830,
      118834,
      118836,
      118862,
      118876,
      118882,
      118884,
      118888,
      118902,
      118926,
      118940,
      118968,
      118978,
      118980,
      118984,
      118992,
      119006,
      119014,
      119020,
      119034,
      119068,
      119096,
      119152,
      119166,
      119170,
      119172,
      119176,
      119184,
      119198,
      119200,
      119228,
      119238,
      119244,
      119256,
      119278,
      119282,
      119284,
      119324,
      119352,
      119408,
      119422,
      119520,
      119548,
      119554,
      119556,
      119560,
      119568,
      119582,
      119584,
      119612,
      119616,
      119672,
      119686,
      119692,
      119704,
      119728,
      119742,
      119758,
      119772,
      119778,
      119780,
      119784,
      119798,
      119920,
      119934,
      120032,
      120060,
      120256,
      120312,
      120324,
      120328,
      120336,
      120352,
      120384,
      120440,
      120560,
      120582,
      120588,
      120600,
      120624,
      120638,
      120672,
      120700,
      120718,
      120732,
      120760,
      120770,
      120772,
      120776,
      120784,
      120798,
      120806,
      120812,
      120870,
      120876,
      120890,
      120902,
      120908,
      120920,
      120946,
      120948,
      120966,
      120972,
      120984,
      121008,
      121022,
      121038,
      121058,
      121060,
      121064,
      121078,
      121100,
      121112,
      121136,
      121150,
      121184,
      121212,
      121244,
      121282,
      121284,
      121288,
      121296,
      121318,
      121338,
      121356,
      121368,
      121392,
      121406,
      121440,
      121468,
      121536,
      121592,
      121656,
      121730,
      121732,
      121736,
      121744,
      121758,
      121760,
      121804,
      121842,
      121844,
      121890,
      121922,
      121924,
      121928,
      121936,
      121950,
      121958,
      121978,
      121986,
      121988,
      121992,
      122e3,
      122014,
      122016,
      122044,
      122060,
      122098,
      122100,
      122116,
      122120,
      122128,
      122142,
      122144,
      122172,
      122176,
      122232,
      122246,
      122264,
      122318,
      122338,
      122340,
      122344,
      122414,
      122418,
      122420,
      122446,
      122460,
      122466,
      122468,
      122472,
      122510,
      122524,
      122552,
      122562,
      122564,
      122568,
      122576,
      122598,
      122618,
      122646,
      122662,
      122668,
      122694,
      122700,
      122712,
      122738,
      122740,
      122762,
      122770,
      122772,
      122786,
      122788,
      122792,
      123018,
      123026,
      123028,
      123042,
      123044,
      123048,
      123062,
      123098,
      123146,
      123154,
      123156,
      123170,
      123172,
      123176,
      123190,
      123202,
      123204,
      123208,
      123216,
      123238,
      123244,
      123258,
      123290,
      123314,
      123316,
      123402,
      123410,
      123412,
      123426,
      123428,
      123432,
      123446,
      123458,
      123464,
      123472,
      123486,
      123494,
      123500,
      123514,
      123522,
      123524,
      123528,
      123536,
      123552,
      123580,
      123590,
      123596,
      123608,
      123630,
      123634,
      123636,
      123674,
      123698,
      123700,
      123740,
      123746,
      123748,
      123752,
      123834,
      123914,
      123922,
      123924,
      123938,
      123944,
      123958,
      123970,
      123976,
      123984,
      123998,
      124006,
      124012,
      124026,
      124034,
      124036,
      124048,
      124062,
      124064,
      124092,
      124102,
      124108,
      124120,
      124142,
      124146,
      124148,
      124162,
      124164,
      124168,
      124176,
      124190,
      124192,
      124220,
      124224,
      124280,
      124294,
      124300,
      124312,
      124336,
      124350,
      124366,
      124380,
      124386,
      124388,
      124392,
      124406,
      124442,
      124462,
      124466,
      124468,
      124494,
      124508,
      124514,
      124520,
      124558,
      124572,
      124600,
      124610,
      124612,
      124616,
      124624,
      124646,
      124666,
      124694,
      124710,
      124716,
      124730,
      124742,
      124748,
      124760,
      124786,
      124788,
      124818,
      124820,
      124834,
      124836,
      124840,
      124854,
      124946,
      124948,
      124962,
      124964,
      124968,
      124982,
      124994,
      124996,
      125e3,
      125008,
      125022,
      125030,
      125036,
      125050,
      125058,
      125060,
      125064,
      125072,
      125086,
      125088,
      125116,
      125126,
      125132,
      125144,
      125166,
      125170,
      125172,
      125186,
      125188,
      125192,
      125200,
      125216,
      125244,
      125248,
      125304,
      125318,
      125324,
      125336,
      125360,
      125374,
      125390,
      125404,
      125410,
      125412,
      125416,
      125430,
      125444,
      125448,
      125456,
      125472,
      125504,
      125560,
      125680,
      125702,
      125708,
      125720,
      125744,
      125758,
      125792,
      125820,
      125838,
      125852,
      125880,
      125890,
      125892,
      125896,
      125904,
      125918,
      125926,
      125932,
      125978,
      125998,
      126002,
      126004,
      126030,
      126044,
      126050,
      126052,
      126056,
      126094,
      126108,
      126136,
      126146,
      126148,
      126152,
      126160,
      126182,
      126202,
      126222,
      126236,
      126264,
      126320,
      126334,
      126338,
      126340,
      126344,
      126352,
      126366,
      126368,
      126412,
      126450,
      126452,
      126486,
      126502,
      126508,
      126522,
      126534,
      126540,
      126552,
      126574,
      126578,
      126580,
      126598,
      126604,
      126616,
      126640,
      126654,
      126670,
      126684,
      126690,
      126692,
      126696,
      126738,
      126754,
      126756,
      126760,
      126774,
      126786,
      126788,
      126792,
      126800,
      126814,
      126822,
      126828,
      126842,
      126894,
      126898,
      126900,
      126934,
      127126,
      127142,
      127148,
      127162,
      127178,
      127186,
      127188,
      127254,
      127270,
      127276,
      127290,
      127302,
      127308,
      127320,
      127342,
      127346,
      127348,
      127370,
      127378,
      127380,
      127394,
      127396,
      127400,
      127450,
      127510,
      127526,
      127532,
      127546,
      127558,
      127576,
      127598,
      127602,
      127604,
      127622,
      127628,
      127640,
      127664,
      127678,
      127694,
      127708,
      127714,
      127716,
      127720,
      127734,
      127754,
      127762,
      127764,
      127778,
      127784,
      127810,
      127812,
      127816,
      127824,
      127838,
      127846,
      127866,
      127898,
      127918,
      127922,
      127924,
      128022,
      128038,
      128044,
      128058,
      128070,
      128076,
      128088,
      128110,
      128114,
      128116,
      128134,
      128140,
      128152,
      128176,
      128190,
      128206,
      128220,
      128226,
      128228,
      128232,
      128246,
      128262,
      128268,
      128280,
      128304,
      128318,
      128352,
      128380,
      128398,
      128412,
      128440,
      128450,
      128452,
      128456,
      128464,
      128478,
      128486,
      128492,
      128506,
      128522,
      128530,
      128532,
      128546,
      128548,
      128552,
      128566,
      128578,
      128580,
      128584,
      128592,
      128606,
      128614,
      128634,
      128642,
      128644,
      128648,
      128656,
      128670,
      128672,
      128700,
      128716,
      128754,
      128756,
      128794,
      128814,
      128818,
      128820,
      128846,
      128860,
      128866,
      128868,
      128872,
      128886,
      128918,
      128934,
      128940,
      128954,
      128978,
      128980,
      129178,
      129198,
      129202,
      129204,
      129238,
      129258,
      129306,
      129326,
      129330,
      129332,
      129358,
      129372,
      129378,
      129380,
      129384,
      129398,
      129430,
      129446,
      129452,
      129466,
      129482,
      129490,
      129492,
      129562,
      129582,
      129586,
      129588,
      129614,
      129628,
      129634,
      129636,
      129640,
      129654,
      129678,
      129692,
      129720,
      129730,
      129732,
      129736,
      129744,
      129758,
      129766,
      129772,
      129814,
      129830,
      129836,
      129850,
      129862,
      129868,
      129880,
      129902,
      129906,
      129908,
      129930,
      129938,
      129940,
      129954,
      129956,
      129960,
      129974,
      130010
    ]), K.CODEWORD_TABLE = Int32Array.from([
      2627,
      1819,
      2622,
      2621,
      1813,
      1812,
      2729,
      2724,
      2723,
      2779,
      2774,
      2773,
      902,
      896,
      908,
      868,
      865,
      861,
      859,
      2511,
      873,
      871,
      1780,
      835,
      2493,
      825,
      2491,
      842,
      837,
      844,
      1764,
      1762,
      811,
      810,
      809,
      2483,
      807,
      2482,
      806,
      2480,
      815,
      814,
      813,
      812,
      2484,
      817,
      816,
      1745,
      1744,
      1742,
      1746,
      2655,
      2637,
      2635,
      2626,
      2625,
      2623,
      2628,
      1820,
      2752,
      2739,
      2737,
      2728,
      2727,
      2725,
      2730,
      2785,
      2783,
      2778,
      2777,
      2775,
      2780,
      787,
      781,
      747,
      739,
      736,
      2413,
      754,
      752,
      1719,
      692,
      689,
      681,
      2371,
      678,
      2369,
      700,
      697,
      694,
      703,
      1688,
      1686,
      642,
      638,
      2343,
      631,
      2341,
      627,
      2338,
      651,
      646,
      643,
      2345,
      654,
      652,
      1652,
      1650,
      1647,
      1654,
      601,
      599,
      2322,
      596,
      2321,
      594,
      2319,
      2317,
      611,
      610,
      608,
      606,
      2324,
      603,
      2323,
      615,
      614,
      612,
      1617,
      1616,
      1614,
      1612,
      616,
      1619,
      1618,
      2575,
      2538,
      2536,
      905,
      901,
      898,
      909,
      2509,
      2507,
      2504,
      870,
      867,
      864,
      860,
      2512,
      875,
      872,
      1781,
      2490,
      2489,
      2487,
      2485,
      1748,
      836,
      834,
      832,
      830,
      2494,
      827,
      2492,
      843,
      841,
      839,
      845,
      1765,
      1763,
      2701,
      2676,
      2674,
      2653,
      2648,
      2656,
      2634,
      2633,
      2631,
      2629,
      1821,
      2638,
      2636,
      2770,
      2763,
      2761,
      2750,
      2745,
      2753,
      2736,
      2735,
      2733,
      2731,
      1848,
      2740,
      2738,
      2786,
      2784,
      591,
      588,
      576,
      569,
      566,
      2296,
      1590,
      537,
      534,
      526,
      2276,
      522,
      2274,
      545,
      542,
      539,
      548,
      1572,
      1570,
      481,
      2245,
      466,
      2242,
      462,
      2239,
      492,
      485,
      482,
      2249,
      496,
      494,
      1534,
      1531,
      1528,
      1538,
      413,
      2196,
      406,
      2191,
      2188,
      425,
      419,
      2202,
      415,
      2199,
      432,
      430,
      427,
      1472,
      1467,
      1464,
      433,
      1476,
      1474,
      368,
      367,
      2160,
      365,
      2159,
      362,
      2157,
      2155,
      2152,
      378,
      377,
      375,
      2166,
      372,
      2165,
      369,
      2162,
      383,
      381,
      379,
      2168,
      1419,
      1418,
      1416,
      1414,
      385,
      1411,
      384,
      1423,
      1422,
      1420,
      1424,
      2461,
      802,
      2441,
      2439,
      790,
      786,
      783,
      794,
      2409,
      2406,
      2403,
      750,
      742,
      738,
      2414,
      756,
      753,
      1720,
      2367,
      2365,
      2362,
      2359,
      1663,
      693,
      691,
      684,
      2373,
      680,
      2370,
      702,
      699,
      696,
      704,
      1690,
      1687,
      2337,
      2336,
      2334,
      2332,
      1624,
      2329,
      1622,
      640,
      637,
      2344,
      634,
      2342,
      630,
      2340,
      650,
      648,
      645,
      2346,
      655,
      653,
      1653,
      1651,
      1649,
      1655,
      2612,
      2597,
      2595,
      2571,
      2568,
      2565,
      2576,
      2534,
      2529,
      2526,
      1787,
      2540,
      2537,
      907,
      904,
      900,
      910,
      2503,
      2502,
      2500,
      2498,
      1768,
      2495,
      1767,
      2510,
      2508,
      2506,
      869,
      866,
      863,
      2513,
      876,
      874,
      1782,
      2720,
      2713,
      2711,
      2697,
      2694,
      2691,
      2702,
      2672,
      2670,
      2664,
      1828,
      2678,
      2675,
      2647,
      2646,
      2644,
      2642,
      1823,
      2639,
      1822,
      2654,
      2652,
      2650,
      2657,
      2771,
      1855,
      2765,
      2762,
      1850,
      1849,
      2751,
      2749,
      2747,
      2754,
      353,
      2148,
      344,
      342,
      336,
      2142,
      332,
      2140,
      345,
      1375,
      1373,
      306,
      2130,
      299,
      2128,
      295,
      2125,
      319,
      314,
      311,
      2132,
      1354,
      1352,
      1349,
      1356,
      262,
      257,
      2101,
      253,
      2096,
      2093,
      274,
      273,
      267,
      2107,
      263,
      2104,
      280,
      278,
      275,
      1316,
      1311,
      1308,
      1320,
      1318,
      2052,
      202,
      2050,
      2044,
      2040,
      219,
      2063,
      212,
      2060,
      208,
      2055,
      224,
      221,
      2066,
      1260,
      1258,
      1252,
      231,
      1248,
      229,
      1266,
      1264,
      1261,
      1268,
      155,
      1998,
      153,
      1996,
      1994,
      1991,
      1988,
      165,
      164,
      2007,
      162,
      2006,
      159,
      2003,
      2e3,
      172,
      171,
      169,
      2012,
      166,
      2010,
      1186,
      1184,
      1182,
      1179,
      175,
      1176,
      173,
      1192,
      1191,
      1189,
      1187,
      176,
      1194,
      1193,
      2313,
      2307,
      2305,
      592,
      589,
      2294,
      2292,
      2289,
      578,
      572,
      568,
      2297,
      580,
      1591,
      2272,
      2267,
      2264,
      1547,
      538,
      536,
      529,
      2278,
      525,
      2275,
      547,
      544,
      541,
      1574,
      1571,
      2237,
      2235,
      2229,
      1493,
      2225,
      1489,
      478,
      2247,
      470,
      2244,
      465,
      2241,
      493,
      488,
      484,
      2250,
      498,
      495,
      1536,
      1533,
      1530,
      1539,
      2187,
      2186,
      2184,
      2182,
      1432,
      2179,
      1430,
      2176,
      1427,
      414,
      412,
      2197,
      409,
      2195,
      405,
      2193,
      2190,
      426,
      424,
      421,
      2203,
      418,
      2201,
      431,
      429,
      1473,
      1471,
      1469,
      1466,
      434,
      1477,
      1475,
      2478,
      2472,
      2470,
      2459,
      2457,
      2454,
      2462,
      803,
      2437,
      2432,
      2429,
      1726,
      2443,
      2440,
      792,
      789,
      785,
      2401,
      2399,
      2393,
      1702,
      2389,
      1699,
      2411,
      2408,
      2405,
      745,
      741,
      2415,
      758,
      755,
      1721,
      2358,
      2357,
      2355,
      2353,
      1661,
      2350,
      1660,
      2347,
      1657,
      2368,
      2366,
      2364,
      2361,
      1666,
      690,
      687,
      2374,
      683,
      2372,
      701,
      698,
      705,
      1691,
      1689,
      2619,
      2617,
      2610,
      2608,
      2605,
      2613,
      2593,
      2588,
      2585,
      1803,
      2599,
      2596,
      2563,
      2561,
      2555,
      1797,
      2551,
      1795,
      2573,
      2570,
      2567,
      2577,
      2525,
      2524,
      2522,
      2520,
      1786,
      2517,
      1785,
      2514,
      1783,
      2535,
      2533,
      2531,
      2528,
      1788,
      2541,
      2539,
      906,
      903,
      911,
      2721,
      1844,
      2715,
      2712,
      1838,
      1836,
      2699,
      2696,
      2693,
      2703,
      1827,
      1826,
      1824,
      2673,
      2671,
      2669,
      2666,
      1829,
      2679,
      2677,
      1858,
      1857,
      2772,
      1854,
      1853,
      1851,
      1856,
      2766,
      2764,
      143,
      1987,
      139,
      1986,
      135,
      133,
      131,
      1984,
      128,
      1983,
      125,
      1981,
      138,
      137,
      136,
      1985,
      1133,
      1132,
      1130,
      112,
      110,
      1974,
      107,
      1973,
      104,
      1971,
      1969,
      122,
      121,
      119,
      117,
      1977,
      114,
      1976,
      124,
      1115,
      1114,
      1112,
      1110,
      1117,
      1116,
      84,
      83,
      1953,
      81,
      1952,
      78,
      1950,
      1948,
      1945,
      94,
      93,
      91,
      1959,
      88,
      1958,
      85,
      1955,
      99,
      97,
      95,
      1961,
      1086,
      1085,
      1083,
      1081,
      1078,
      100,
      1090,
      1089,
      1087,
      1091,
      49,
      47,
      1917,
      44,
      1915,
      1913,
      1910,
      1907,
      59,
      1926,
      56,
      1925,
      53,
      1922,
      1919,
      66,
      64,
      1931,
      61,
      1929,
      1042,
      1040,
      1038,
      71,
      1035,
      70,
      1032,
      68,
      1048,
      1047,
      1045,
      1043,
      1050,
      1049,
      12,
      10,
      1869,
      1867,
      1864,
      1861,
      21,
      1880,
      19,
      1877,
      1874,
      1871,
      28,
      1888,
      25,
      1886,
      22,
      1883,
      982,
      980,
      977,
      974,
      32,
      30,
      991,
      989,
      987,
      984,
      34,
      995,
      994,
      992,
      2151,
      2150,
      2147,
      2146,
      2144,
      356,
      355,
      354,
      2149,
      2139,
      2138,
      2136,
      2134,
      1359,
      343,
      341,
      338,
      2143,
      335,
      2141,
      348,
      347,
      346,
      1376,
      1374,
      2124,
      2123,
      2121,
      2119,
      1326,
      2116,
      1324,
      310,
      308,
      305,
      2131,
      302,
      2129,
      298,
      2127,
      320,
      318,
      316,
      313,
      2133,
      322,
      321,
      1355,
      1353,
      1351,
      1357,
      2092,
      2091,
      2089,
      2087,
      1276,
      2084,
      1274,
      2081,
      1271,
      259,
      2102,
      256,
      2100,
      252,
      2098,
      2095,
      272,
      269,
      2108,
      266,
      2106,
      281,
      279,
      277,
      1317,
      1315,
      1313,
      1310,
      282,
      1321,
      1319,
      2039,
      2037,
      2035,
      2032,
      1203,
      2029,
      1200,
      1197,
      207,
      2053,
      205,
      2051,
      201,
      2049,
      2046,
      2043,
      220,
      218,
      2064,
      215,
      2062,
      211,
      2059,
      228,
      226,
      223,
      2069,
      1259,
      1257,
      1254,
      232,
      1251,
      230,
      1267,
      1265,
      1263,
      2316,
      2315,
      2312,
      2311,
      2309,
      2314,
      2304,
      2303,
      2301,
      2299,
      1593,
      2308,
      2306,
      590,
      2288,
      2287,
      2285,
      2283,
      1578,
      2280,
      1577,
      2295,
      2293,
      2291,
      579,
      577,
      574,
      571,
      2298,
      582,
      581,
      1592,
      2263,
      2262,
      2260,
      2258,
      1545,
      2255,
      1544,
      2252,
      1541,
      2273,
      2271,
      2269,
      2266,
      1550,
      535,
      532,
      2279,
      528,
      2277,
      546,
      543,
      549,
      1575,
      1573,
      2224,
      2222,
      2220,
      1486,
      2217,
      1485,
      2214,
      1482,
      1479,
      2238,
      2236,
      2234,
      2231,
      1496,
      2228,
      1492,
      480,
      477,
      2248,
      473,
      2246,
      469,
      2243,
      490,
      487,
      2251,
      497,
      1537,
      1535,
      1532,
      2477,
      2476,
      2474,
      2479,
      2469,
      2468,
      2466,
      2464,
      1730,
      2473,
      2471,
      2453,
      2452,
      2450,
      2448,
      1729,
      2445,
      1728,
      2460,
      2458,
      2456,
      2463,
      805,
      804,
      2428,
      2427,
      2425,
      2423,
      1725,
      2420,
      1724,
      2417,
      1722,
      2438,
      2436,
      2434,
      2431,
      1727,
      2444,
      2442,
      793,
      791,
      788,
      795,
      2388,
      2386,
      2384,
      1697,
      2381,
      1696,
      2378,
      1694,
      1692,
      2402,
      2400,
      2398,
      2395,
      1703,
      2392,
      1701,
      2412,
      2410,
      2407,
      751,
      748,
      744,
      2416,
      759,
      757,
      1807,
      2620,
      2618,
      1806,
      1805,
      2611,
      2609,
      2607,
      2614,
      1802,
      1801,
      1799,
      2594,
      2592,
      2590,
      2587,
      1804,
      2600,
      2598,
      1794,
      1793,
      1791,
      1789,
      2564,
      2562,
      2560,
      2557,
      1798,
      2554,
      1796,
      2574,
      2572,
      2569,
      2578,
      1847,
      1846,
      2722,
      1843,
      1842,
      1840,
      1845,
      2716,
      2714,
      1835,
      1834,
      1832,
      1830,
      1839,
      1837,
      2700,
      2698,
      2695,
      2704,
      1817,
      1811,
      1810,
      897,
      862,
      1777,
      829,
      826,
      838,
      1760,
      1758,
      808,
      2481,
      1741,
      1740,
      1738,
      1743,
      2624,
      1818,
      2726,
      2776,
      782,
      740,
      737,
      1715,
      686,
      679,
      695,
      1682,
      1680,
      639,
      628,
      2339,
      647,
      644,
      1645,
      1643,
      1640,
      1648,
      602,
      600,
      597,
      595,
      2320,
      593,
      2318,
      609,
      607,
      604,
      1611,
      1610,
      1608,
      1606,
      613,
      1615,
      1613,
      2328,
      926,
      924,
      892,
      886,
      899,
      857,
      850,
      2505,
      1778,
      824,
      823,
      821,
      819,
      2488,
      818,
      2486,
      833,
      831,
      828,
      840,
      1761,
      1759,
      2649,
      2632,
      2630,
      2746,
      2734,
      2732,
      2782,
      2781,
      570,
      567,
      1587,
      531,
      527,
      523,
      540,
      1566,
      1564,
      476,
      467,
      463,
      2240,
      486,
      483,
      1524,
      1521,
      1518,
      1529,
      411,
      403,
      2192,
      399,
      2189,
      423,
      416,
      1462,
      1457,
      1454,
      428,
      1468,
      1465,
      2210,
      366,
      363,
      2158,
      360,
      2156,
      357,
      2153,
      376,
      373,
      370,
      2163,
      1410,
      1409,
      1407,
      1405,
      382,
      1402,
      380,
      1417,
      1415,
      1412,
      1421,
      2175,
      2174,
      777,
      774,
      771,
      784,
      732,
      725,
      722,
      2404,
      743,
      1716,
      676,
      674,
      668,
      2363,
      665,
      2360,
      685,
      1684,
      1681,
      626,
      624,
      622,
      2335,
      620,
      2333,
      617,
      2330,
      641,
      635,
      649,
      1646,
      1644,
      1642,
      2566,
      928,
      925,
      2530,
      2527,
      894,
      891,
      888,
      2501,
      2499,
      2496,
      858,
      856,
      854,
      851,
      1779,
      2692,
      2668,
      2665,
      2645,
      2643,
      2640,
      2651,
      2768,
      2759,
      2757,
      2744,
      2743,
      2741,
      2748,
      352,
      1382,
      340,
      337,
      333,
      1371,
      1369,
      307,
      300,
      296,
      2126,
      315,
      312,
      1347,
      1342,
      1350,
      261,
      258,
      250,
      2097,
      246,
      2094,
      271,
      268,
      264,
      1306,
      1301,
      1298,
      276,
      1312,
      1309,
      2115,
      203,
      2048,
      195,
      2045,
      191,
      2041,
      213,
      209,
      2056,
      1246,
      1244,
      1238,
      225,
      1234,
      222,
      1256,
      1253,
      1249,
      1262,
      2080,
      2079,
      154,
      1997,
      150,
      1995,
      147,
      1992,
      1989,
      163,
      160,
      2004,
      156,
      2001,
      1175,
      1174,
      1172,
      1170,
      1167,
      170,
      1164,
      167,
      1185,
      1183,
      1180,
      1177,
      174,
      1190,
      1188,
      2025,
      2024,
      2022,
      587,
      586,
      564,
      559,
      556,
      2290,
      573,
      1588,
      520,
      518,
      512,
      2268,
      508,
      2265,
      530,
      1568,
      1565,
      461,
      457,
      2233,
      450,
      2230,
      446,
      2226,
      479,
      471,
      489,
      1526,
      1523,
      1520,
      397,
      395,
      2185,
      392,
      2183,
      389,
      2180,
      2177,
      410,
      2194,
      402,
      422,
      1463,
      1461,
      1459,
      1456,
      1470,
      2455,
      799,
      2433,
      2430,
      779,
      776,
      773,
      2397,
      2394,
      2390,
      734,
      728,
      724,
      746,
      1717,
      2356,
      2354,
      2351,
      2348,
      1658,
      677,
      675,
      673,
      670,
      667,
      688,
      1685,
      1683,
      2606,
      2589,
      2586,
      2559,
      2556,
      2552,
      927,
      2523,
      2521,
      2518,
      2515,
      1784,
      2532,
      895,
      893,
      890,
      2718,
      2709,
      2707,
      2689,
      2687,
      2684,
      2663,
      2662,
      2660,
      2658,
      1825,
      2667,
      2769,
      1852,
      2760,
      2758,
      142,
      141,
      1139,
      1138,
      134,
      132,
      129,
      126,
      1982,
      1129,
      1128,
      1126,
      1131,
      113,
      111,
      108,
      105,
      1972,
      101,
      1970,
      120,
      118,
      115,
      1109,
      1108,
      1106,
      1104,
      123,
      1113,
      1111,
      82,
      79,
      1951,
      75,
      1949,
      72,
      1946,
      92,
      89,
      86,
      1956,
      1077,
      1076,
      1074,
      1072,
      98,
      1069,
      96,
      1084,
      1082,
      1079,
      1088,
      1968,
      1967,
      48,
      45,
      1916,
      42,
      1914,
      39,
      1911,
      1908,
      60,
      57,
      54,
      1923,
      50,
      1920,
      1031,
      1030,
      1028,
      1026,
      67,
      1023,
      65,
      1020,
      62,
      1041,
      1039,
      1036,
      1033,
      69,
      1046,
      1044,
      1944,
      1943,
      1941,
      11,
      9,
      1868,
      7,
      1865,
      1862,
      1859,
      20,
      1878,
      16,
      1875,
      13,
      1872,
      970,
      968,
      966,
      963,
      29,
      960,
      26,
      23,
      983,
      981,
      978,
      975,
      33,
      971,
      31,
      990,
      988,
      985,
      1906,
      1904,
      1902,
      993,
      351,
      2145,
      1383,
      331,
      330,
      328,
      326,
      2137,
      323,
      2135,
      339,
      1372,
      1370,
      294,
      293,
      291,
      289,
      2122,
      286,
      2120,
      283,
      2117,
      309,
      303,
      317,
      1348,
      1346,
      1344,
      245,
      244,
      242,
      2090,
      239,
      2088,
      236,
      2085,
      2082,
      260,
      2099,
      249,
      270,
      1307,
      1305,
      1303,
      1300,
      1314,
      189,
      2038,
      186,
      2036,
      183,
      2033,
      2030,
      2026,
      206,
      198,
      2047,
      194,
      216,
      1247,
      1245,
      1243,
      1240,
      227,
      1237,
      1255,
      2310,
      2302,
      2300,
      2286,
      2284,
      2281,
      565,
      563,
      561,
      558,
      575,
      1589,
      2261,
      2259,
      2256,
      2253,
      1542,
      521,
      519,
      517,
      514,
      2270,
      511,
      533,
      1569,
      1567,
      2223,
      2221,
      2218,
      2215,
      1483,
      2211,
      1480,
      459,
      456,
      453,
      2232,
      449,
      474,
      491,
      1527,
      1525,
      1522,
      2475,
      2467,
      2465,
      2451,
      2449,
      2446,
      801,
      800,
      2426,
      2424,
      2421,
      2418,
      1723,
      2435,
      780,
      778,
      775,
      2387,
      2385,
      2382,
      2379,
      1695,
      2375,
      1693,
      2396,
      735,
      733,
      730,
      727,
      749,
      1718,
      2616,
      2615,
      2604,
      2603,
      2601,
      2584,
      2583,
      2581,
      2579,
      1800,
      2591,
      2550,
      2549,
      2547,
      2545,
      1792,
      2542,
      1790,
      2558,
      929,
      2719,
      1841,
      2710,
      2708,
      1833,
      1831,
      2690,
      2688,
      2686,
      1815,
      1809,
      1808,
      1774,
      1756,
      1754,
      1737,
      1736,
      1734,
      1739,
      1816,
      1711,
      1676,
      1674,
      633,
      629,
      1638,
      1636,
      1633,
      1641,
      598,
      1605,
      1604,
      1602,
      1600,
      605,
      1609,
      1607,
      2327,
      887,
      853,
      1775,
      822,
      820,
      1757,
      1755,
      1584,
      524,
      1560,
      1558,
      468,
      464,
      1514,
      1511,
      1508,
      1519,
      408,
      404,
      400,
      1452,
      1447,
      1444,
      417,
      1458,
      1455,
      2208,
      364,
      361,
      358,
      2154,
      1401,
      1400,
      1398,
      1396,
      374,
      1393,
      371,
      1408,
      1406,
      1403,
      1413,
      2173,
      2172,
      772,
      726,
      723,
      1712,
      672,
      669,
      666,
      682,
      1678,
      1675,
      625,
      623,
      621,
      618,
      2331,
      636,
      632,
      1639,
      1637,
      1635,
      920,
      918,
      884,
      880,
      889,
      849,
      848,
      847,
      846,
      2497,
      855,
      852,
      1776,
      2641,
      2742,
      2787,
      1380,
      334,
      1367,
      1365,
      301,
      297,
      1340,
      1338,
      1335,
      1343,
      255,
      251,
      247,
      1296,
      1291,
      1288,
      265,
      1302,
      1299,
      2113,
      204,
      196,
      192,
      2042,
      1232,
      1230,
      1224,
      214,
      1220,
      210,
      1242,
      1239,
      1235,
      1250,
      2077,
      2075,
      151,
      148,
      1993,
      144,
      1990,
      1163,
      1162,
      1160,
      1158,
      1155,
      161,
      1152,
      157,
      1173,
      1171,
      1168,
      1165,
      168,
      1181,
      1178,
      2021,
      2020,
      2018,
      2023,
      585,
      560,
      557,
      1585,
      516,
      509,
      1562,
      1559,
      458,
      447,
      2227,
      472,
      1516,
      1513,
      1510,
      398,
      396,
      393,
      390,
      2181,
      386,
      2178,
      407,
      1453,
      1451,
      1449,
      1446,
      420,
      1460,
      2209,
      769,
      764,
      720,
      712,
      2391,
      729,
      1713,
      664,
      663,
      661,
      659,
      2352,
      656,
      2349,
      671,
      1679,
      1677,
      2553,
      922,
      919,
      2519,
      2516,
      885,
      883,
      881,
      2685,
      2661,
      2659,
      2767,
      2756,
      2755,
      140,
      1137,
      1136,
      130,
      127,
      1125,
      1124,
      1122,
      1127,
      109,
      106,
      102,
      1103,
      1102,
      1100,
      1098,
      116,
      1107,
      1105,
      1980,
      80,
      76,
      73,
      1947,
      1068,
      1067,
      1065,
      1063,
      90,
      1060,
      87,
      1075,
      1073,
      1070,
      1080,
      1966,
      1965,
      46,
      43,
      40,
      1912,
      36,
      1909,
      1019,
      1018,
      1016,
      1014,
      58,
      1011,
      55,
      1008,
      51,
      1029,
      1027,
      1024,
      1021,
      63,
      1037,
      1034,
      1940,
      1939,
      1937,
      1942,
      8,
      1866,
      4,
      1863,
      1,
      1860,
      956,
      954,
      952,
      949,
      946,
      17,
      14,
      969,
      967,
      964,
      961,
      27,
      957,
      24,
      979,
      976,
      972,
      1901,
      1900,
      1898,
      1896,
      986,
      1905,
      1903,
      350,
      349,
      1381,
      329,
      327,
      324,
      1368,
      1366,
      292,
      290,
      287,
      284,
      2118,
      304,
      1341,
      1339,
      1337,
      1345,
      243,
      240,
      237,
      2086,
      233,
      2083,
      254,
      1297,
      1295,
      1293,
      1290,
      1304,
      2114,
      190,
      187,
      184,
      2034,
      180,
      2031,
      177,
      2027,
      199,
      1233,
      1231,
      1229,
      1226,
      217,
      1223,
      1241,
      2078,
      2076,
      584,
      555,
      554,
      552,
      550,
      2282,
      562,
      1586,
      507,
      506,
      504,
      502,
      2257,
      499,
      2254,
      515,
      1563,
      1561,
      445,
      443,
      441,
      2219,
      438,
      2216,
      435,
      2212,
      460,
      454,
      475,
      1517,
      1515,
      1512,
      2447,
      798,
      797,
      2422,
      2419,
      770,
      768,
      766,
      2383,
      2380,
      2376,
      721,
      719,
      717,
      714,
      731,
      1714,
      2602,
      2582,
      2580,
      2548,
      2546,
      2543,
      923,
      921,
      2717,
      2706,
      2705,
      2683,
      2682,
      2680,
      1771,
      1752,
      1750,
      1733,
      1732,
      1731,
      1735,
      1814,
      1707,
      1670,
      1668,
      1631,
      1629,
      1626,
      1634,
      1599,
      1598,
      1596,
      1594,
      1603,
      1601,
      2326,
      1772,
      1753,
      1751,
      1581,
      1554,
      1552,
      1504,
      1501,
      1498,
      1509,
      1442,
      1437,
      1434,
      401,
      1448,
      1445,
      2206,
      1392,
      1391,
      1389,
      1387,
      1384,
      359,
      1399,
      1397,
      1394,
      1404,
      2171,
      2170,
      1708,
      1672,
      1669,
      619,
      1632,
      1630,
      1628,
      1773,
      1378,
      1363,
      1361,
      1333,
      1328,
      1336,
      1286,
      1281,
      1278,
      248,
      1292,
      1289,
      2111,
      1218,
      1216,
      1210,
      197,
      1206,
      193,
      1228,
      1225,
      1221,
      1236,
      2073,
      2071,
      1151,
      1150,
      1148,
      1146,
      152,
      1143,
      149,
      1140,
      145,
      1161,
      1159,
      1156,
      1153,
      158,
      1169,
      1166,
      2017,
      2016,
      2014,
      2019,
      1582,
      510,
      1556,
      1553,
      452,
      448,
      1506,
      1500,
      394,
      391,
      387,
      1443,
      1441,
      1439,
      1436,
      1450,
      2207,
      765,
      716,
      713,
      1709,
      662,
      660,
      657,
      1673,
      1671,
      916,
      914,
      879,
      878,
      877,
      882,
      1135,
      1134,
      1121,
      1120,
      1118,
      1123,
      1097,
      1096,
      1094,
      1092,
      103,
      1101,
      1099,
      1979,
      1059,
      1058,
      1056,
      1054,
      77,
      1051,
      74,
      1066,
      1064,
      1061,
      1071,
      1964,
      1963,
      1007,
      1006,
      1004,
      1002,
      999,
      41,
      996,
      37,
      1017,
      1015,
      1012,
      1009,
      52,
      1025,
      1022,
      1936,
      1935,
      1933,
      1938,
      942,
      940,
      938,
      935,
      932,
      5,
      2,
      955,
      953,
      950,
      947,
      18,
      943,
      15,
      965,
      962,
      958,
      1895,
      1894,
      1892,
      1890,
      973,
      1899,
      1897,
      1379,
      325,
      1364,
      1362,
      288,
      285,
      1334,
      1332,
      1330,
      241,
      238,
      234,
      1287,
      1285,
      1283,
      1280,
      1294,
      2112,
      188,
      185,
      181,
      178,
      2028,
      1219,
      1217,
      1215,
      1212,
      200,
      1209,
      1227,
      2074,
      2072,
      583,
      553,
      551,
      1583,
      505,
      503,
      500,
      513,
      1557,
      1555,
      444,
      442,
      439,
      436,
      2213,
      455,
      451,
      1507,
      1505,
      1502,
      796,
      763,
      762,
      760,
      767,
      711,
      710,
      708,
      706,
      2377,
      718,
      715,
      1710,
      2544,
      917,
      915,
      2681,
      1627,
      1597,
      1595,
      2325,
      1769,
      1749,
      1747,
      1499,
      1438,
      1435,
      2204,
      1390,
      1388,
      1385,
      1395,
      2169,
      2167,
      1704,
      1665,
      1662,
      1625,
      1623,
      1620,
      1770,
      1329,
      1282,
      1279,
      2109,
      1214,
      1207,
      1222,
      2068,
      2065,
      1149,
      1147,
      1144,
      1141,
      146,
      1157,
      1154,
      2013,
      2011,
      2008,
      2015,
      1579,
      1549,
      1546,
      1495,
      1487,
      1433,
      1431,
      1428,
      1425,
      388,
      1440,
      2205,
      1705,
      658,
      1667,
      1664,
      1119,
      1095,
      1093,
      1978,
      1057,
      1055,
      1052,
      1062,
      1962,
      1960,
      1005,
      1003,
      1e3,
      997,
      38,
      1013,
      1010,
      1932,
      1930,
      1927,
      1934,
      941,
      939,
      936,
      933,
      6,
      930,
      3,
      951,
      948,
      944,
      1889,
      1887,
      1884,
      1881,
      959,
      1893,
      1891,
      35,
      1377,
      1360,
      1358,
      1327,
      1325,
      1322,
      1331,
      1277,
      1275,
      1272,
      1269,
      235,
      1284,
      2110,
      1205,
      1204,
      1201,
      1198,
      182,
      1195,
      179,
      1213,
      2070,
      2067,
      1580,
      501,
      1551,
      1548,
      440,
      437,
      1497,
      1494,
      1490,
      1503,
      761,
      709,
      707,
      1706,
      913,
      912,
      2198,
      1386,
      2164,
      2161,
      1621,
      1766,
      2103,
      1208,
      2058,
      2054,
      1145,
      1142,
      2005,
      2002,
      1999,
      2009,
      1488,
      1429,
      1426,
      2200,
      1698,
      1659,
      1656,
      1975,
      1053,
      1957,
      1954,
      1001,
      998,
      1924,
      1921,
      1918,
      1928,
      937,
      934,
      931,
      1879,
      1876,
      1873,
      1870,
      945,
      1885,
      1882,
      1323,
      1273,
      1270,
      2105,
      1202,
      1199,
      1196,
      1211,
      2061,
      2057,
      1576,
      1543,
      1540,
      1484,
      1481,
      1478,
      1491,
      1700
    ]);
    class $r {
      constructor(e, t) {
        this.bits = e, this.points = t;
      }
      getBits() {
        return this.bits;
      }
      getPoints() {
        return this.points;
      }
    }
    class ee {
      /**
       * <p>Detects a PDF417 Code in an image. Only checks 0 and 180 degree rotations.</p>
       *
       * @param image barcode image to decode
       * @param hints optional hints to detector
       * @param multiple if true, then the image is searched for multiple codes. If false, then at most one code will
       * be found and returned
       * @return {@link PDF417DetectorResult} encapsulating results of detecting a PDF417 code
       * @throws NotFoundException if no PDF417 Code can be found
       */
      static detectMultiple(e, t, n) {
        let r = e.getBlackMatrix(), i = ee.detect(n, r);
        return i.length || (r = r.clone(), r.rotate180(), i = ee.detect(n, r)), new $r(r, i);
      }
      /**
       * Detects PDF417 codes in an image. Only checks 0 degree rotation
       * @param multiple if true, then the image is searched for multiple codes. If false, then at most one code will
       * be found and returned
       * @param bitMatrix bit matrix to detect barcodes in
       * @return List of ResultPoint arrays containing the coordinates of found barcodes
       */
      static detect(e, t) {
        const n = new Array();
        let r = 0, i = 0, s = !1;
        for (; r < t.getHeight(); ) {
          const o = ee.findVertices(t, r, i);
          if (o[0] == null && o[3] == null) {
            if (!s)
              break;
            s = !1, i = 0;
            for (const a of n)
              a[1] != null && (r = Math.trunc(Math.max(r, a[1].getY()))), a[3] != null && (r = Math.max(r, Math.trunc(a[3].getY())));
            r += ee.ROW_STEP;
            continue;
          }
          if (s = !0, n.push(o), !e)
            break;
          o[2] != null ? (i = Math.trunc(o[2].getX()), r = Math.trunc(o[2].getY())) : (i = Math.trunc(o[4].getX()), r = Math.trunc(o[4].getY()));
        }
        return n;
      }
      /**
       * Locate the vertices and the codewords area of a black blob using the Start
       * and Stop patterns as locators.
       *
       * @param matrix the scanned barcode image.
       * @return an array containing the vertices:
       *           vertices[0] x, y top left barcode
       *           vertices[1] x, y bottom left barcode
       *           vertices[2] x, y top right barcode
       *           vertices[3] x, y bottom right barcode
       *           vertices[4] x, y top left codeword area
       *           vertices[5] x, y bottom left codeword area
       *           vertices[6] x, y top right codeword area
       *           vertices[7] x, y bottom right codeword area
       */
      static findVertices(e, t, n) {
        const r = e.getHeight(), i = e.getWidth(), s = new Array(8);
        return ee.copyToResult(s, ee.findRowsWithPattern(e, r, i, t, n, ee.START_PATTERN), ee.INDEXES_START_PATTERN), s[4] != null && (n = Math.trunc(s[4].getX()), t = Math.trunc(s[4].getY())), ee.copyToResult(s, ee.findRowsWithPattern(e, r, i, t, n, ee.STOP_PATTERN), ee.INDEXES_STOP_PATTERN), s;
      }
      static copyToResult(e, t, n) {
        for (let r = 0; r < n.length; r++)
          e[n[r]] = t[r];
      }
      static findRowsWithPattern(e, t, n, r, i, s) {
        const o = new Array(4);
        let a = !1;
        const l = new Int32Array(s.length);
        for (; r < t; r += ee.ROW_STEP) {
          let f = ee.findGuardPattern(e, i, r, n, !1, s, l);
          if (f != null) {
            for (; r > 0; ) {
              const d = ee.findGuardPattern(e, i, --r, n, !1, s, l);
              if (d != null)
                f = d;
              else {
                r++;
                break;
              }
            }
            o[0] = new W(f[0], r), o[1] = new W(f[1], r), a = !0;
            break;
          }
        }
        let c = r + 1;
        if (a) {
          let f = 0, d = Int32Array.from([Math.trunc(o[0].getX()), Math.trunc(o[1].getX())]);
          for (; c < t; c++) {
            const C = ee.findGuardPattern(e, d[0], c, n, !1, s, l);
            if (C != null && Math.abs(d[0] - C[0]) < ee.MAX_PATTERN_DRIFT && Math.abs(d[1] - C[1]) < ee.MAX_PATTERN_DRIFT)
              d = C, f = 0;
            else {
              if (f > ee.SKIPPED_ROW_COUNT_MAX)
                break;
              f++;
            }
          }
          c -= f + 1, o[2] = new W(d[0], c), o[3] = new W(d[1], c);
        }
        return c - r < ee.BARCODE_MIN_HEIGHT && me.fill(o, null), o;
      }
      /**
       * @param matrix row of black/white values to search
       * @param column x position to start search
       * @param row y position to start search
       * @param width the number of pixels to search on this row
       * @param pattern pattern of counts of number of black and white pixels that are
       *                 being searched for as a pattern
       * @param counters array of counters, as long as pattern, to re-use
       * @return start/end horizontal offset of guard pattern, as an array of two ints.
       */
      static findGuardPattern(e, t, n, r, i, s, o) {
        me.fillWithin(o, 0, o.length, 0);
        let a = t, l = 0;
        for (; e.get(a, n) && a > 0 && l++ < ee.MAX_PIXEL_DRIFT; )
          a--;
        let c = a, f = 0, d = s.length;
        for (let C = i; c < r; c++)
          if (e.get(c, n) !== C)
            o[f]++;
          else {
            if (f === d - 1) {
              if (ee.patternMatchVariance(o, s, ee.MAX_INDIVIDUAL_VARIANCE) < ee.MAX_AVG_VARIANCE)
                return new Int32Array([a, c]);
              a += o[0] + o[1], re.arraycopy(o, 2, o, 0, f - 1), o[f - 1] = 0, o[f] = 0, f--;
            } else
              f++;
            o[f] = 1, C = !C;
          }
        return f === d - 1 && ee.patternMatchVariance(o, s, ee.MAX_INDIVIDUAL_VARIANCE) < ee.MAX_AVG_VARIANCE ? new Int32Array([a, c - 1]) : null;
      }
      /**
       * Determines how closely a set of observed counts of runs of black/white
       * values matches a given target pattern. This is reported as the ratio of
       * the total variance from the expected pattern proportions across all
       * pattern elements, to the length of the pattern.
       *
       * @param counters observed counters
       * @param pattern expected pattern
       * @param maxIndividualVariance The most any counter can differ before we give up
       * @return ratio of total variance between counters and pattern compared to total pattern size
       */
      static patternMatchVariance(e, t, n) {
        let r = e.length, i = 0, s = 0;
        for (let l = 0; l < r; l++)
          i += e[l], s += t[l];
        if (i < s)
          return (
            /*Float.POSITIVE_INFINITY*/
            1 / 0
          );
        let o = i / s;
        n *= o;
        let a = 0;
        for (let l = 0; l < r; l++) {
          let c = e[l], f = t[l] * o, d = c > f ? c - f : f - c;
          if (d > n)
            return (
              /*Float.POSITIVE_INFINITY*/
              1 / 0
            );
          a += d;
        }
        return a / i;
      }
    }
    ee.INDEXES_START_PATTERN = Int32Array.from([0, 4, 1, 5]), ee.INDEXES_STOP_PATTERN = Int32Array.from([6, 2, 7, 3]), ee.MAX_AVG_VARIANCE = 0.42, ee.MAX_INDIVIDUAL_VARIANCE = 0.8, ee.START_PATTERN = Int32Array.from([8, 1, 1, 1, 1, 1, 1, 3]), ee.STOP_PATTERN = Int32Array.from([7, 1, 1, 3, 1, 1, 1, 2, 1]), ee.MAX_PIXEL_DRIFT = 3, ee.MAX_PATTERN_DRIFT = 5, ee.SKIPPED_ROW_COUNT_MAX = 25, ee.ROW_STEP = 5, ee.BARCODE_MIN_HEIGHT = 10;
    class We {
      constructor(e, t) {
        if (t.length === 0)
          throw new D();
        this.field = e;
        let n = (
          /*int*/
          t.length
        );
        if (n > 1 && t[0] === 0) {
          let r = (
            /*int*/
            1
          );
          for (; r < n && t[r] === 0; )
            r++;
          r === n ? this.coefficients = new Int32Array([0]) : (this.coefficients = new Int32Array(n - r), re.arraycopy(t, r, this.coefficients, 0, this.coefficients.length));
        } else
          this.coefficients = t;
      }
      getCoefficients() {
        return this.coefficients;
      }
      /**
       * @return degree of this polynomial
       */
      getDegree() {
        return this.coefficients.length - 1;
      }
      /**
       * @return true iff this polynomial is the monomial "0"
       */
      isZero() {
        return this.coefficients[0] === 0;
      }
      /**
       * @return coefficient of x^degree term in this polynomial
       */
      getCoefficient(e) {
        return this.coefficients[this.coefficients.length - 1 - e];
      }
      /**
       * @return evaluation of this polynomial at a given point
       */
      evaluateAt(e) {
        if (e === 0)
          return this.getCoefficient(0);
        if (e === 1) {
          let r = (
            /*int*/
            0
          );
          for (let i of this.coefficients)
            r = this.field.add(r, i);
          return r;
        }
        let t = (
          /*int*/
          this.coefficients[0]
        ), n = (
          /*int*/
          this.coefficients.length
        );
        for (let r = 1; r < n; r++)
          t = this.field.add(this.field.multiply(e, t), this.coefficients[r]);
        return t;
      }
      add(e) {
        if (!this.field.equals(e.field))
          throw new D("ModulusPolys do not have same ModulusGF field");
        if (this.isZero())
          return e;
        if (e.isZero())
          return this;
        let t = this.coefficients, n = e.coefficients;
        if (t.length > n.length) {
          let s = t;
          t = n, n = s;
        }
        let r = new Int32Array(n.length), i = (
          /*int*/
          n.length - t.length
        );
        re.arraycopy(n, 0, r, 0, i);
        for (let s = i; s < n.length; s++)
          r[s] = this.field.add(t[s - i], n[s]);
        return new We(this.field, r);
      }
      subtract(e) {
        if (!this.field.equals(e.field))
          throw new D("ModulusPolys do not have same ModulusGF field");
        return e.isZero() ? this : this.add(e.negative());
      }
      multiply(e) {
        return e instanceof We ? this.multiplyOther(e) : this.multiplyScalar(e);
      }
      multiplyOther(e) {
        if (!this.field.equals(e.field))
          throw new D("ModulusPolys do not have same ModulusGF field");
        if (this.isZero() || e.isZero())
          return new We(this.field, new Int32Array([0]));
        let t = this.coefficients, n = (
          /*int*/
          t.length
        ), r = e.coefficients, i = (
          /*int*/
          r.length
        ), s = new Int32Array(n + i - 1);
        for (let o = 0; o < n; o++) {
          let a = (
            /*int*/
            t[o]
          );
          for (let l = 0; l < i; l++)
            s[o + l] = this.field.add(s[o + l], this.field.multiply(a, r[l]));
        }
        return new We(this.field, s);
      }
      negative() {
        let e = (
          /*int*/
          this.coefficients.length
        ), t = new Int32Array(e);
        for (let n = 0; n < e; n++)
          t[n] = this.field.subtract(0, this.coefficients[n]);
        return new We(this.field, t);
      }
      multiplyScalar(e) {
        if (e === 0)
          return new We(this.field, new Int32Array([0]));
        if (e === 1)
          return this;
        let t = (
          /*int*/
          this.coefficients.length
        ), n = new Int32Array(t);
        for (let r = 0; r < t; r++)
          n[r] = this.field.multiply(this.coefficients[r], e);
        return new We(this.field, n);
      }
      multiplyByMonomial(e, t) {
        if (e < 0)
          throw new D();
        if (t === 0)
          return new We(this.field, new Int32Array([0]));
        let n = (
          /*int*/
          this.coefficients.length
        ), r = new Int32Array(n + e);
        for (let i = 0; i < n; i++)
          r[i] = this.field.multiply(this.coefficients[i], t);
        return new We(this.field, r);
      }
      /*
        ModulusPoly[] divide(other: ModulusPoly) {
          if (!field.equals(other.field)) {
            throw new IllegalArgumentException("ModulusPolys do not have same ModulusGF field");
          }
          if (other.isZero()) {
            throw new IllegalArgumentException("Divide by 0");
          }
      
          let quotient: ModulusPoly = field.getZero();
          let remainder: ModulusPoly = this;
      
          let denominatorLeadingTerm: /*int/ number = other.getCoefficient(other.getDegree());
          let inverseDenominatorLeadingTerm: /*int/ number = field.inverse(denominatorLeadingTerm);
      
          while (remainder.getDegree() >= other.getDegree() && !remainder.isZero()) {
            let degreeDifference: /*int/ number = remainder.getDegree() - other.getDegree();
            let scale: /*int/ number = field.multiply(remainder.getCoefficient(remainder.getDegree()), inverseDenominatorLeadingTerm);
            let term: ModulusPoly = other.multiplyByMonomial(degreeDifference, scale);
            let iterationQuotient: ModulusPoly = field.buildMonomial(degreeDifference, scale);
            quotient = quotient.add(iterationQuotient);
            remainder = remainder.subtract(term);
          }
      
          return new ModulusPoly[] { quotient, remainder };
        }
        */
      // @Override
      toString() {
        let e = new ge(
          /*8 * this.getDegree()*/
        );
        for (let t = this.getDegree(); t >= 0; t--) {
          let n = (
            /*int*/
            this.getCoefficient(t)
          );
          n !== 0 && (n < 0 ? (e.append(" - "), n = -n) : e.length() > 0 && e.append(" + "), (t === 0 || n !== 1) && e.append(n), t !== 0 && (t === 1 ? e.append("x") : (e.append("x^"), e.append(t))));
        }
        return e.toString();
      }
    }
    class ei {
      add(e, t) {
        return (e + t) % this.modulus;
      }
      subtract(e, t) {
        return (this.modulus + e - t) % this.modulus;
      }
      exp(e) {
        return this.expTable[e];
      }
      log(e) {
        if (e === 0)
          throw new D();
        return this.logTable[e];
      }
      inverse(e) {
        if (e === 0)
          throw new An();
        return this.expTable[this.modulus - this.logTable[e] - 1];
      }
      multiply(e, t) {
        return e === 0 || t === 0 ? 0 : this.expTable[(this.logTable[e] + this.logTable[t]) % (this.modulus - 1)];
      }
      getSize() {
        return this.modulus;
      }
      equals(e) {
        return e === this;
      }
    }
    class Kn extends ei {
      // private /*final*/ modulus: /*int*/ number;
      constructor(e, t) {
        super(), this.modulus = e, this.expTable = new Int32Array(e), this.logTable = new Int32Array(e);
        let n = (
          /*int*/
          1
        );
        for (let r = 0; r < e; r++)
          this.expTable[r] = n, n = n * t % e;
        for (let r = 0; r < e - 1; r++)
          this.logTable[this.expTable[r]] = r;
        this.zero = new We(this, new Int32Array([0])), this.one = new We(this, new Int32Array([1]));
      }
      getZero() {
        return this.zero;
      }
      getOne() {
        return this.one;
      }
      buildMonomial(e, t) {
        if (e < 0)
          throw new D();
        if (t === 0)
          return this.zero;
        let n = new Int32Array(e + 1);
        return n[0] = t, new We(this, n);
      }
    }
    Kn.PDF417_GF = new Kn(K.NUMBER_OF_CODEWORDS, 3);
    class xr {
      constructor() {
        this.field = Kn.PDF417_GF;
      }
      /**
       * @param received received codewords
       * @param numECCodewords number of those codewords used for EC
       * @param erasures location of erasures
       * @return number of errors
       * @throws ChecksumException if errors cannot be corrected, maybe because of too many errors
       */
      decode(e, t, n) {
        let r = new We(this.field, e), i = new Int32Array(t), s = !1;
        for (let p = t; p > 0; p--) {
          let b = r.evaluateAt(this.field.exp(p));
          i[t - p] = b, b !== 0 && (s = !0);
        }
        if (!s)
          return 0;
        let o = this.field.getOne();
        if (n != null)
          for (const p of n) {
            let b = this.field.exp(e.length - 1 - p), y = new We(this.field, new Int32Array([this.field.subtract(0, b), 1]));
            o = o.multiply(y);
          }
        let a = new We(this.field, i), l = this.runEuclideanAlgorithm(this.field.buildMonomial(t, 1), a, t), c = l[0], f = l[1], d = this.findErrorLocations(c), C = this.findErrorMagnitudes(f, c, d);
        for (let p = 0; p < d.length; p++) {
          let b = e.length - 1 - this.field.log(d[p]);
          if (b < 0)
            throw J.getChecksumInstance();
          e[b] = this.field.subtract(e[b], C[p]);
        }
        return d.length;
      }
      /**
       *
       * @param ModulusPoly
       * @param a
       * @param ModulusPoly
       * @param b
       * @param int
       * @param R
       * @throws ChecksumException
       */
      runEuclideanAlgorithm(e, t, n) {
        if (e.getDegree() < t.getDegree()) {
          let d = e;
          e = t, t = d;
        }
        let r = e, i = t, s = this.field.getZero(), o = this.field.getOne();
        for (; i.getDegree() >= Math.round(n / 2); ) {
          let d = r, C = s;
          if (r = i, s = o, r.isZero())
            throw J.getChecksumInstance();
          i = d;
          let p = this.field.getZero(), b = r.getCoefficient(r.getDegree()), y = this.field.inverse(b);
          for (; i.getDegree() >= r.getDegree() && !i.isZero(); ) {
            let _ = i.getDegree() - r.getDegree(), P = this.field.multiply(i.getCoefficient(i.getDegree()), y);
            p = p.add(this.field.buildMonomial(_, P)), i = i.subtract(r.multiplyByMonomial(_, P));
          }
          o = p.multiply(s).subtract(C).negative();
        }
        let a = o.getCoefficient(0);
        if (a === 0)
          throw J.getChecksumInstance();
        let l = this.field.inverse(a), c = o.multiply(l), f = i.multiply(l);
        return [c, f];
      }
      /**
       *
       * @param errorLocator
       * @throws ChecksumException
       */
      findErrorLocations(e) {
        let t = e.getDegree(), n = new Int32Array(t), r = 0;
        for (let i = 1; i < this.field.getSize() && r < t; i++)
          e.evaluateAt(i) === 0 && (n[r] = this.field.inverse(i), r++);
        if (r !== t)
          throw J.getChecksumInstance();
        return n;
      }
      findErrorMagnitudes(e, t, n) {
        let r = t.getDegree(), i = new Int32Array(r);
        for (let l = 1; l <= r; l++)
          i[r - l] = this.field.multiply(l, t.getCoefficient(l));
        let s = new We(this.field, i), o = n.length, a = new Int32Array(o);
        for (let l = 0; l < o; l++) {
          let c = this.field.inverse(n[l]), f = this.field.subtract(0, e.evaluateAt(c)), d = this.field.inverse(s.evaluateAt(c));
          a[l] = this.field.multiply(f, d);
        }
        return a;
      }
    }
    class Ut {
      constructor(e, t, n, r, i) {
        e instanceof Ut ? this.constructor_2(e) : this.constructor_1(e, t, n, r, i);
      }
      /**
       *
       * @param image
       * @param topLeft
       * @param bottomLeft
       * @param topRight
       * @param bottomRight
       *
       * @throws NotFoundException
       */
      constructor_1(e, t, n, r, i) {
        const s = t == null || n == null, o = r == null || i == null;
        if (s && o)
          throw new O();
        s ? (t = new W(0, r.getY()), n = new W(0, i.getY())) : o && (r = new W(e.getWidth() - 1, t.getY()), i = new W(e.getWidth() - 1, n.getY())), this.image = e, this.topLeft = t, this.bottomLeft = n, this.topRight = r, this.bottomRight = i, this.minX = Math.trunc(Math.min(t.getX(), n.getX())), this.maxX = Math.trunc(Math.max(r.getX(), i.getX())), this.minY = Math.trunc(Math.min(t.getY(), r.getY())), this.maxY = Math.trunc(Math.max(n.getY(), i.getY()));
      }
      constructor_2(e) {
        this.image = e.image, this.topLeft = e.getTopLeft(), this.bottomLeft = e.getBottomLeft(), this.topRight = e.getTopRight(), this.bottomRight = e.getBottomRight(), this.minX = e.getMinX(), this.maxX = e.getMaxX(), this.minY = e.getMinY(), this.maxY = e.getMaxY();
      }
      /**
       * @throws NotFoundException
       */
      static merge(e, t) {
        return e == null ? t : t == null ? e : new Ut(e.image, e.topLeft, e.bottomLeft, t.topRight, t.bottomRight);
      }
      /**
       * @throws NotFoundException
       */
      addMissingRows(e, t, n) {
        let r = this.topLeft, i = this.bottomLeft, s = this.topRight, o = this.bottomRight;
        if (e > 0) {
          let a = n ? this.topLeft : this.topRight, l = Math.trunc(a.getY() - e);
          l < 0 && (l = 0);
          let c = new W(a.getX(), l);
          n ? r = c : s = c;
        }
        if (t > 0) {
          let a = n ? this.bottomLeft : this.bottomRight, l = Math.trunc(a.getY() + t);
          l >= this.image.getHeight() && (l = this.image.getHeight() - 1);
          let c = new W(a.getX(), l);
          n ? i = c : o = c;
        }
        return new Ut(this.image, r, i, s, o);
      }
      getMinX() {
        return this.minX;
      }
      getMaxX() {
        return this.maxX;
      }
      getMinY() {
        return this.minY;
      }
      getMaxY() {
        return this.maxY;
      }
      getTopLeft() {
        return this.topLeft;
      }
      getTopRight() {
        return this.topRight;
      }
      getBottomLeft() {
        return this.bottomLeft;
      }
      getBottomRight() {
        return this.bottomRight;
      }
    }
    class ti {
      constructor(e, t, n, r) {
        this.columnCount = e, this.errorCorrectionLevel = r, this.rowCountUpperPart = t, this.rowCountLowerPart = n, this.rowCount = t + n;
      }
      getColumnCount() {
        return this.columnCount;
      }
      getErrorCorrectionLevel() {
        return this.errorCorrectionLevel;
      }
      getRowCount() {
        return this.rowCount;
      }
      getRowCountUpperPart() {
        return this.rowCountUpperPart;
      }
      getRowCountLowerPart() {
        return this.rowCountLowerPart;
      }
    }
    class ln {
      constructor() {
        this.buffer = "";
      }
      /**
       *
       * @see https://stackoverflow.com/a/13439711/4367683
       *
       * @param str
       * @param arr
       */
      static form(e, t) {
        let n = -1;
        function r(s, o, a, l, c, f) {
          if (s === "%%")
            return "%";
          if (t[++n] === void 0)
            return;
          s = l ? parseInt(l.substr(1)) : void 0;
          let d = c ? parseInt(c.substr(1)) : void 0, C;
          switch (f) {
            case "s":
              C = t[n];
              break;
            case "c":
              C = t[n][0];
              break;
            case "f":
              C = parseFloat(t[n]).toFixed(s);
              break;
            case "p":
              C = parseFloat(t[n]).toPrecision(s);
              break;
            case "e":
              C = parseFloat(t[n]).toExponential(s);
              break;
            case "x":
              C = parseInt(t[n]).toString(d || 16);
              break;
            case "d":
              C = parseFloat(parseInt(t[n], d || 10).toPrecision(s)).toFixed(0);
              break;
          }
          C = typeof C == "object" ? JSON.stringify(C) : (+C).toString(d);
          let p = parseInt(a), b = a && a[0] + "" == "0" ? "0" : " ";
          for (; C.length < p; )
            C = o !== void 0 ? C + b : b + C;
          return C;
        }
        let i = /%(-)?(0?[0-9]+)?([.][0-9]+)?([#][0-9]+)?([scfpexd%])/g;
        return e.replace(i, r);
      }
      /**
       *
       * @param append The new string to append.
       * @param args Argumets values to be formated.
       */
      format(e, ...t) {
        this.buffer += ln.form(e, t);
      }
      /**
       * Returns the Formatter string value.
       */
      toString() {
        return this.buffer;
      }
    }
    class cn {
      constructor(e) {
        this.boundingBox = new Ut(e), this.codewords = new Array(e.getMaxY() - e.getMinY() + 1);
      }
      /*final*/
      getCodewordNearby(e) {
        let t = this.getCodeword(e);
        if (t != null)
          return t;
        for (let n = 1; n < cn.MAX_NEARBY_DISTANCE; n++) {
          let r = this.imageRowToCodewordIndex(e) - n;
          if (r >= 0 && (t = this.codewords[r], t != null) || (r = this.imageRowToCodewordIndex(e) + n, r < this.codewords.length && (t = this.codewords[r], t != null)))
            return t;
        }
        return null;
      }
      /*final int*/
      imageRowToCodewordIndex(e) {
        return e - this.boundingBox.getMinY();
      }
      /*final void*/
      setCodeword(e, t) {
        this.codewords[this.imageRowToCodewordIndex(e)] = t;
      }
      /*final*/
      getCodeword(e) {
        return this.codewords[this.imageRowToCodewordIndex(e)];
      }
      /*final*/
      getBoundingBox() {
        return this.boundingBox;
      }
      /*final*/
      getCodewords() {
        return this.codewords;
      }
      // @Override
      toString() {
        const e = new ln();
        let t = 0;
        for (const n of this.codewords) {
          if (n == null) {
            e.format("%3d:    |   %n", t++);
            continue;
          }
          e.format("%3d: %3d|%3d%n", t++, n.getRowNumber(), n.getValue());
        }
        return e.toString();
      }
    }
    cn.MAX_NEARBY_DISTANCE = 5;
    class hn {
      constructor() {
        this.values = /* @__PURE__ */ new Map();
      }
      /**
       * Add an occurrence of a value
       */
      setValue(e) {
        e = Math.trunc(e);
        let t = this.values.get(e);
        t == null && (t = 0), t++, this.values.set(e, t);
      }
      /**
       * Determines the maximum occurrence of a set value and returns all values which were set with this occurrence.
       * @return an array of int, containing the values with the highest occurrence, or null, if no value was set
       */
      getValue() {
        let e = -1, t = new Array();
        for (const [n, r] of this.values.entries()) {
          const i = {
            getKey: () => n,
            getValue: () => r
          };
          i.getValue() > e ? (e = i.getValue(), t = [], t.push(i.getKey())) : i.getValue() === e && t.push(i.getKey());
        }
        return K.toIntArray(t);
      }
      getConfidence(e) {
        return this.values.get(e);
      }
    }
    class gr extends cn {
      constructor(e, t) {
        super(e), this._isLeft = t;
      }
      setRowNumbers() {
        for (let e of this.getCodewords())
          e != null && e.setRowNumberAsRowIndicatorColumn();
      }
      // TODO implement properly
      // TODO maybe we should add missing codewords to store the correct row number to make
      // finding row numbers for other columns easier
      // use row height count to make detection of invalid row numbers more reliable
      adjustCompleteIndicatorColumnRowNumbers(e) {
        let t = this.getCodewords();
        this.setRowNumbers(), this.removeIncorrectCodewords(t, e);
        let n = this.getBoundingBox(), r = this._isLeft ? n.getTopLeft() : n.getTopRight(), i = this._isLeft ? n.getBottomLeft() : n.getBottomRight(), s = this.imageRowToCodewordIndex(Math.trunc(r.getY())), o = this.imageRowToCodewordIndex(Math.trunc(i.getY())), a = -1, l = 1, c = 0;
        for (let f = s; f < o; f++) {
          if (t[f] == null)
            continue;
          let d = t[f], C = d.getRowNumber() - a;
          if (C === 0)
            c++;
          else if (C === 1)
            l = Math.max(l, c), c = 1, a = d.getRowNumber();
          else if (C < 0 || d.getRowNumber() >= e.getRowCount() || C > f)
            t[f] = null;
          else {
            let p;
            l > 2 ? p = (l - 2) * C : p = C;
            let b = p >= f;
            for (let y = 1; y <= p && !b; y++)
              b = t[f - y] != null;
            b ? t[f] = null : (a = d.getRowNumber(), c = 1);
          }
        }
      }
      getRowHeights() {
        let e = this.getBarcodeMetadata();
        if (e == null)
          return null;
        this.adjustIncompleteIndicatorColumnRowNumbers(e);
        let t = new Int32Array(e.getRowCount());
        for (let n of this.getCodewords())
          if (n != null) {
            let r = n.getRowNumber();
            if (r >= t.length)
              continue;
            t[r]++;
          }
        return t;
      }
      // TODO maybe we should add missing codewords to store the correct row number to make
      // finding row numbers for other columns easier
      // use row height count to make detection of invalid row numbers more reliable
      adjustIncompleteIndicatorColumnRowNumbers(e) {
        let t = this.getBoundingBox(), n = this._isLeft ? t.getTopLeft() : t.getTopRight(), r = this._isLeft ? t.getBottomLeft() : t.getBottomRight(), i = this.imageRowToCodewordIndex(Math.trunc(n.getY())), s = this.imageRowToCodewordIndex(Math.trunc(r.getY())), o = this.getCodewords(), a = -1;
        for (let l = i; l < s; l++) {
          if (o[l] == null)
            continue;
          let c = o[l];
          c.setRowNumberAsRowIndicatorColumn();
          let f = c.getRowNumber() - a;
          f === 0 || (f === 1 ? a = c.getRowNumber() : c.getRowNumber() >= e.getRowCount() ? o[l] = null : a = c.getRowNumber());
        }
      }
      getBarcodeMetadata() {
        let e = this.getCodewords(), t = new hn(), n = new hn(), r = new hn(), i = new hn();
        for (let o of e) {
          if (o == null)
            continue;
          o.setRowNumberAsRowIndicatorColumn();
          let a = o.getValue() % 30, l = o.getRowNumber();
          switch (this._isLeft || (l += 2), l % 3) {
            case 0:
              n.setValue(a * 3 + 1);
              break;
            case 1:
              i.setValue(a / 3), r.setValue(a % 3);
              break;
            case 2:
              t.setValue(a + 1);
              break;
          }
        }
        if (t.getValue().length === 0 || n.getValue().length === 0 || r.getValue().length === 0 || i.getValue().length === 0 || t.getValue()[0] < 1 || n.getValue()[0] + r.getValue()[0] < K.MIN_ROWS_IN_BARCODE || n.getValue()[0] + r.getValue()[0] > K.MAX_ROWS_IN_BARCODE)
          return null;
        let s = new ti(t.getValue()[0], n.getValue()[0], r.getValue()[0], i.getValue()[0]);
        return this.removeIncorrectCodewords(e, s), s;
      }
      removeIncorrectCodewords(e, t) {
        for (let n = 0; n < e.length; n++) {
          let r = e[n];
          if (e[n] == null)
            continue;
          let i = r.getValue() % 30, s = r.getRowNumber();
          if (s > t.getRowCount()) {
            e[n] = null;
            continue;
          }
          switch (this._isLeft || (s += 2), s % 3) {
            case 0:
              i * 3 + 1 !== t.getRowCountUpperPart() && (e[n] = null);
              break;
            case 1:
              (Math.trunc(i / 3) !== t.getErrorCorrectionLevel() || i % 3 !== t.getRowCountLowerPart()) && (e[n] = null);
              break;
            case 2:
              i + 1 !== t.getColumnCount() && (e[n] = null);
              break;
          }
        }
      }
      isLeft() {
        return this._isLeft;
      }
      // @Override
      toString() {
        return "IsLeft: " + this._isLeft + `
` + super.toString();
      }
    }
    class un {
      constructor(e, t) {
        this.ADJUST_ROW_NUMBER_SKIP = 2, this.barcodeMetadata = e, this.barcodeColumnCount = e.getColumnCount(), this.boundingBox = t, this.detectionResultColumns = new Array(this.barcodeColumnCount + 2);
      }
      getDetectionResultColumns() {
        this.adjustIndicatorColumnRowNumbers(this.detectionResultColumns[0]), this.adjustIndicatorColumnRowNumbers(this.detectionResultColumns[this.barcodeColumnCount + 1]);
        let e = K.MAX_CODEWORDS_IN_BARCODE, t;
        do
          t = e, e = this.adjustRowNumbersAndGetCount();
        while (e > 0 && e < t);
        return this.detectionResultColumns;
      }
      adjustIndicatorColumnRowNumbers(e) {
        e != null && e.adjustCompleteIndicatorColumnRowNumbers(this.barcodeMetadata);
      }
      // TODO ensure that no detected codewords with unknown row number are left
      // we should be able to estimate the row height and use it as a hint for the row number
      // we should also fill the rows top to bottom and bottom to top
      /**
       * @return number of codewords which don't have a valid row number. Note that the count is not accurate as codewords
       * will be counted several times. It just serves as an indicator to see when we can stop adjusting row numbers
       */
      adjustRowNumbersAndGetCount() {
        let e = this.adjustRowNumbersByRow();
        if (e === 0)
          return 0;
        for (let t = 1; t < this.barcodeColumnCount + 1; t++) {
          let n = this.detectionResultColumns[t].getCodewords();
          for (let r = 0; r < n.length; r++)
            n[r] != null && (n[r].hasValidRowNumber() || this.adjustRowNumbers(t, r, n));
        }
        return e;
      }
      adjustRowNumbersByRow() {
        return this.adjustRowNumbersFromBothRI(), this.adjustRowNumbersFromLRI() + this.adjustRowNumbersFromRRI();
      }
      adjustRowNumbersFromBothRI() {
        if (this.detectionResultColumns[0] == null || this.detectionResultColumns[this.barcodeColumnCount + 1] == null)
          return;
        let e = this.detectionResultColumns[0].getCodewords(), t = this.detectionResultColumns[this.barcodeColumnCount + 1].getCodewords();
        for (let n = 0; n < e.length; n++)
          if (e[n] != null && t[n] != null && e[n].getRowNumber() === t[n].getRowNumber())
            for (let r = 1; r <= this.barcodeColumnCount; r++) {
              let i = this.detectionResultColumns[r].getCodewords()[n];
              i != null && (i.setRowNumber(e[n].getRowNumber()), i.hasValidRowNumber() || (this.detectionResultColumns[r].getCodewords()[n] = null));
            }
      }
      adjustRowNumbersFromRRI() {
        if (this.detectionResultColumns[this.barcodeColumnCount + 1] == null)
          return 0;
        let e = 0, t = this.detectionResultColumns[this.barcodeColumnCount + 1].getCodewords();
        for (let n = 0; n < t.length; n++) {
          if (t[n] == null)
            continue;
          let r = t[n].getRowNumber(), i = 0;
          for (let s = this.barcodeColumnCount + 1; s > 0 && i < this.ADJUST_ROW_NUMBER_SKIP; s--) {
            let o = this.detectionResultColumns[s].getCodewords()[n];
            o != null && (i = un.adjustRowNumberIfValid(r, i, o), o.hasValidRowNumber() || e++);
          }
        }
        return e;
      }
      adjustRowNumbersFromLRI() {
        if (this.detectionResultColumns[0] == null)
          return 0;
        let e = 0, t = this.detectionResultColumns[0].getCodewords();
        for (let n = 0; n < t.length; n++) {
          if (t[n] == null)
            continue;
          let r = t[n].getRowNumber(), i = 0;
          for (let s = 1; s < this.barcodeColumnCount + 1 && i < this.ADJUST_ROW_NUMBER_SKIP; s++) {
            let o = this.detectionResultColumns[s].getCodewords()[n];
            o != null && (i = un.adjustRowNumberIfValid(r, i, o), o.hasValidRowNumber() || e++);
          }
        }
        return e;
      }
      static adjustRowNumberIfValid(e, t, n) {
        return n == null || n.hasValidRowNumber() || (n.isValidRowNumber(e) ? (n.setRowNumber(e), t = 0) : ++t), t;
      }
      adjustRowNumbers(e, t, n) {
        if (!this.detectionResultColumns[e - 1])
          return;
        let r = n[t], i = this.detectionResultColumns[e - 1].getCodewords(), s = i;
        this.detectionResultColumns[e + 1] != null && (s = this.detectionResultColumns[e + 1].getCodewords());
        let o = new Array(14);
        o[2] = i[t], o[3] = s[t], t > 0 && (o[0] = n[t - 1], o[4] = i[t - 1], o[5] = s[t - 1]), t > 1 && (o[8] = n[t - 2], o[10] = i[t - 2], o[11] = s[t - 2]), t < n.length - 1 && (o[1] = n[t + 1], o[6] = i[t + 1], o[7] = s[t + 1]), t < n.length - 2 && (o[9] = n[t + 2], o[12] = i[t + 2], o[13] = s[t + 2]);
        for (let a of o)
          if (un.adjustRowNumber(r, a))
            return;
      }
      /**
       * @return true, if row number was adjusted, false otherwise
       */
      static adjustRowNumber(e, t) {
        return t == null ? !1 : t.hasValidRowNumber() && t.getBucket() === e.getBucket() ? (e.setRowNumber(t.getRowNumber()), !0) : !1;
      }
      getBarcodeColumnCount() {
        return this.barcodeColumnCount;
      }
      getBarcodeRowCount() {
        return this.barcodeMetadata.getRowCount();
      }
      getBarcodeECLevel() {
        return this.barcodeMetadata.getErrorCorrectionLevel();
      }
      setBoundingBox(e) {
        this.boundingBox = e;
      }
      getBoundingBox() {
        return this.boundingBox;
      }
      setDetectionResultColumn(e, t) {
        this.detectionResultColumns[e] = t;
      }
      getDetectionResultColumn(e) {
        return this.detectionResultColumns[e];
      }
      // @Override
      toString() {
        let e = this.detectionResultColumns[0];
        e == null && (e = this.detectionResultColumns[this.barcodeColumnCount + 1]);
        let t = new ln();
        for (let n = 0; n < e.getCodewords().length; n++) {
          t.format("CW %3d:", n);
          for (let r = 0; r < this.barcodeColumnCount + 2; r++) {
            if (this.detectionResultColumns[r] == null) {
              t.format("    |   ");
              continue;
            }
            let i = this.detectionResultColumns[r].getCodewords()[n];
            if (i == null) {
              t.format("    |   ");
              continue;
            }
            t.format(" %3d|%3d", i.getRowNumber(), i.getValue());
          }
          t.format("%n");
        }
        return t.toString();
      }
    }
    class fn {
      constructor(e, t, n, r) {
        this.rowNumber = fn.BARCODE_ROW_UNKNOWN, this.startX = Math.trunc(e), this.endX = Math.trunc(t), this.bucket = Math.trunc(n), this.value = Math.trunc(r);
      }
      hasValidRowNumber() {
        return this.isValidRowNumber(this.rowNumber);
      }
      isValidRowNumber(e) {
        return e !== fn.BARCODE_ROW_UNKNOWN && this.bucket === e % 3 * 3;
      }
      setRowNumberAsRowIndicatorColumn() {
        this.rowNumber = Math.trunc(Math.trunc(this.value / 30) * 3 + Math.trunc(this.bucket / 3));
      }
      getWidth() {
        return this.endX - this.startX;
      }
      getStartX() {
        return this.startX;
      }
      getEndX() {
        return this.endX;
      }
      getBucket() {
        return this.bucket;
      }
      getValue() {
        return this.value;
      }
      getRowNumber() {
        return this.rowNumber;
      }
      setRowNumber(e) {
        this.rowNumber = e;
      }
      //   @Override
      toString() {
        return this.rowNumber + "|" + this.value;
      }
    }
    fn.BARCODE_ROW_UNKNOWN = -1;
    class nt {
      /* @note
       * this action have to be performed before first use of class
       * - static constructor
       * working with 32bit float (based from Java logic)
      */
      static initialize() {
        for (let e = 0; e < K.SYMBOL_TABLE.length; e++) {
          let t = K.SYMBOL_TABLE[e], n = t & 1;
          for (let r = 0; r < K.BARS_IN_MODULE; r++) {
            let i = 0;
            for (; (t & 1) === n; )
              i += 1, t >>= 1;
            n = t & 1, nt.RATIOS_TABLE[e] || (nt.RATIOS_TABLE[e] = new Array(K.BARS_IN_MODULE)), nt.RATIOS_TABLE[e][K.BARS_IN_MODULE - r - 1] = Math.fround(i / K.MODULES_IN_CODEWORD);
          }
        }
        this.bSymbolTableReady = !0;
      }
      static getDecodedValue(e) {
        let t = nt.getDecodedCodewordValue(nt.sampleBitCounts(e));
        return t !== -1 ? t : nt.getClosestDecodedValue(e);
      }
      static sampleBitCounts(e) {
        let t = oe.sum(e), n = new Int32Array(K.BARS_IN_MODULE), r = 0, i = 0;
        for (let s = 0; s < K.MODULES_IN_CODEWORD; s++) {
          let o = t / (2 * K.MODULES_IN_CODEWORD) + s * t / K.MODULES_IN_CODEWORD;
          i + e[r] <= o && (i += e[r], r++), n[r]++;
        }
        return n;
      }
      static getDecodedCodewordValue(e) {
        let t = nt.getBitValue(e);
        return K.getCodeword(t) === -1 ? -1 : t;
      }
      static getBitValue(e) {
        let t = (
          /*long*/
          0
        );
        for (let n = 0; n < e.length; n++)
          for (let r = 0; r < e[n]; r++)
            t = t << 1 | (n % 2 === 0 ? 1 : 0);
        return Math.trunc(t);
      }
      // working with 32bit float (as in Java)
      static getClosestDecodedValue(e) {
        let t = oe.sum(e), n = new Array(K.BARS_IN_MODULE);
        if (t > 1)
          for (let s = 0; s < n.length; s++)
            n[s] = Math.fround(e[s] / t);
        let r = En.MAX_VALUE, i = -1;
        this.bSymbolTableReady || nt.initialize();
        for (let s = 0; s < nt.RATIOS_TABLE.length; s++) {
          let o = 0, a = nt.RATIOS_TABLE[s];
          for (let l = 0; l < K.BARS_IN_MODULE; l++) {
            let c = Math.fround(a[l] - n[l]);
            if (o += Math.fround(c * c), o >= r)
              break;
          }
          o < r && (r = o, i = K.SYMBOL_TABLE[s]);
        }
        return i;
      }
    }
    nt.bSymbolTableReady = !1, nt.RATIOS_TABLE = new Array(K.SYMBOL_TABLE.length).map((u) => new Array(K.BARS_IN_MODULE));
    class wr {
      constructor() {
        this.segmentCount = -1, this.fileSize = -1, this.timestamp = -1, this.checksum = -1;
      }
      /**
       * The Segment ID represents the segment of the whole file distributed over different symbols.
       *
       * @return File segment index
       */
      getSegmentIndex() {
        return this.segmentIndex;
      }
      setSegmentIndex(e) {
        this.segmentIndex = e;
      }
      /**
       * Is the same for each related PDF417 symbol
       *
       * @return File ID
       */
      getFileId() {
        return this.fileId;
      }
      setFileId(e) {
        this.fileId = e;
      }
      /**
       * @return always null
       * @deprecated use dedicated already parsed fields
       */
      //   @Deprecated
      getOptionalData() {
        return this.optionalData;
      }
      /**
       * @param optionalData old optional data format as int array
       * @deprecated parse and use new fields
       */
      //   @Deprecated
      setOptionalData(e) {
        this.optionalData = e;
      }
      /**
       * @return true if it is the last segment
       */
      isLastSegment() {
        return this.lastSegment;
      }
      setLastSegment(e) {
        this.lastSegment = e;
      }
      /**
       * @return count of segments, -1 if not set
       */
      getSegmentCount() {
        return this.segmentCount;
      }
      setSegmentCount(e) {
        this.segmentCount = e;
      }
      getSender() {
        return this.sender || null;
      }
      setSender(e) {
        this.sender = e;
      }
      getAddressee() {
        return this.addressee || null;
      }
      setAddressee(e) {
        this.addressee = e;
      }
      /**
       * Filename of the encoded file
       *
       * @return filename
       */
      getFileName() {
        return this.fileName;
      }
      setFileName(e) {
        this.fileName = e;
      }
      /**
       * filesize in bytes of the encoded file
       *
       * @return filesize in bytes, -1 if not set
       */
      getFileSize() {
        return this.fileSize;
      }
      setFileSize(e) {
        this.fileSize = e;
      }
      /**
       * 16-bit CRC checksum using CCITT-16
       *
       * @return crc checksum, -1 if not set
       */
      getChecksum() {
        return this.checksum;
      }
      setChecksum(e) {
        this.checksum = e;
      }
      /**
       * unix epock timestamp, elapsed seconds since 1970-01-01
       *
       * @return elapsed seconds, -1 if not set
       */
      getTimestamp() {
        return this.timestamp;
      }
      setTimestamp(e) {
        this.timestamp = e;
      }
    }
    class Ar {
      /**
       * Parses a string to a number, since JS has no really Int64.
       *
       * @param num Numeric string.
       * @param radix Destination radix.
       */
      static parseLong(e, t = void 0) {
        return parseInt(e, t);
      }
    }
    class Er extends E {
    }
    Er.kind = "NullPointerException";
    class ni {
      /**
       * Writes <code>b.length</code> bytes from the specified byte array
       * to this output stream. The general contract for <code>write(b)</code>
       * is that it should have exactly the same effect as the call
       * <code>write(b, 0, b.length)</code>.
       *
       * @param      b   the data.
       * @exception  IOException  if an I/O error occurs.
       * @see        java.io.OutputStream#write(byte[], int, int)
       */
      writeBytes(e) {
        this.writeBytesOffset(e, 0, e.length);
      }
      /**
       * Writes <code>len</code> bytes from the specified byte array
       * starting at offset <code>off</code> to this output stream.
       * The general contract for <code>write(b, off, len)</code> is that
       * some of the bytes in the array <code>b</code> are written to the
       * output stream in order; element <code>b[off]</code> is the first
       * byte written and <code>b[off+len-1]</code> is the last byte written
       * by this operation.
       * <p>
       * The <code>write</code> method of <code>OutputStream</code> calls
       * the write method of one argument on each of the bytes to be
       * written out. Subclasses are encouraged to override this method and
       * provide a more efficient implementation.
       * <p>
       * If <code>b</code> is <code>null</code>, a
       * <code>NullPointerException</code> is thrown.
       * <p>
       * If <code>off</code> is negative, or <code>len</code> is negative, or
       * <code>off+len</code> is greater than the length of the array
       * <code>b</code>, then an <tt>IndexOutOfBoundsException</tt> is thrown.
       *
       * @param      b     the data.
       * @param      off   the start offset in the data.
       * @param      len   the number of bytes to write.
       * @exception  IOException  if an I/O error occurs. In particular,
       *             an <code>IOException</code> is thrown if the output
       *             stream is closed.
       */
      writeBytesOffset(e, t, n) {
        if (e == null)
          throw new Er();
        if (t < 0 || t > e.length || n < 0 || t + n > e.length || t + n < 0)
          throw new xt();
        if (n === 0)
          return;
        for (let r = 0; r < n; r++)
          this.write(e[t + r]);
      }
      /**
       * Flushes this output stream and forces any buffered output bytes
       * to be written out. The general contract of <code>flush</code> is
       * that calling it is an indication that, if any bytes previously
       * written have been buffered by the implementation of the output
       * stream, such bytes should immediately be written to their
       * intended destination.
       * <p>
       * If the intended destination of this stream is an abstraction provided by
       * the underlying operating system, for example a file, then flushing the
       * stream guarantees only that bytes previously written to the stream are
       * passed to the operating system for writing; it does not guarantee that
       * they are actually written to a physical device such as a disk drive.
       * <p>
       * The <code>flush</code> method of <code>OutputStream</code> does nothing.
       *
       * @exception  IOException  if an I/O error occurs.
       */
      flush() {
      }
      /**
       * Closes this output stream and releases any system resources
       * associated with this stream. The general contract of <code>close</code>
       * is that it closes the output stream. A closed stream cannot perform
       * output operations and cannot be reopened.
       * <p>
       * The <code>close</code> method of <code>OutputStream</code> does nothing.
       *
       * @exception  IOException  if an I/O error occurs.
       */
      close() {
      }
    }
    class ri extends E {
    }
    class ii extends ni {
      /**
       * Creates a new byte array output stream. The buffer capacity is
       * initially 32 bytes, though its size increases if necessary.
       */
      // public constructor() {
      //     this(32);
      // }
      /**
       * Creates a new byte array output stream, with a buffer capacity of
       * the specified size, in bytes.
       *
       * @param   size   the initial size.
       * @exception  IllegalArgumentException if size is negative.
       */
      constructor(e = 32) {
        if (super(), this.count = 0, e < 0)
          throw new D("Negative initial size: " + e);
        this.buf = new Uint8Array(e);
      }
      /**
       * Increases the capacity if necessary to ensure that it can hold
       * at least the number of elements specified by the minimum
       * capacity argument.
       *
       * @param minCapacity the desired minimum capacity
       * @throws OutOfMemoryError if {@code minCapacity < 0}.  This is
       * interpreted as a request for the unsatisfiably large capacity
       * {@code (long) Integer.MAX_VALUE + (minCapacity - Integer.MAX_VALUE)}.
       */
      ensureCapacity(e) {
        e - this.buf.length > 0 && this.grow(e);
      }
      /**
       * Increases the capacity to ensure that it can hold at least the
       * number of elements specified by the minimum capacity argument.
       *
       * @param minCapacity the desired minimum capacity
       */
      grow(e) {
        let n = this.buf.length << 1;
        if (n - e < 0 && (n = e), n < 0) {
          if (e < 0)
            throw new ri();
          n = j.MAX_VALUE;
        }
        this.buf = me.copyOfUint8Array(this.buf, n);
      }
      /**
       * Writes the specified byte to this byte array output stream.
       *
       * @param   b   the byte to be written.
       */
      write(e) {
        this.ensureCapacity(this.count + 1), this.buf[this.count] = /*(byte)*/
        e, this.count += 1;
      }
      /**
       * Writes <code>len</code> bytes from the specified byte array
       * starting at offset <code>off</code> to this byte array output stream.
       *
       * @param   b     the data.
       * @param   off   the start offset in the data.
       * @param   len   the number of bytes to write.
       */
      writeBytesOffset(e, t, n) {
        if (t < 0 || t > e.length || n < 0 || t + n - e.length > 0)
          throw new xt();
        this.ensureCapacity(this.count + n), re.arraycopy(e, t, this.buf, this.count, n), this.count += n;
      }
      /**
       * Writes the complete contents of this byte array output stream to
       * the specified output stream argument, as if by calling the output
       * stream's write method using <code>out.write(buf, 0, count)</code>.
       *
       * @param      out   the output stream to which to write the data.
       * @exception  IOException  if an I/O error occurs.
       */
      writeTo(e) {
        e.writeBytesOffset(this.buf, 0, this.count);
      }
      /**
       * Resets the <code>count</code> field of this byte array output
       * stream to zero, so that all currently accumulated output in the
       * output stream is discarded. The output stream can be used again,
       * reusing the already allocated buffer space.
       *
       * @see     java.io.ByteArrayInputStream#count
       */
      reset() {
        this.count = 0;
      }
      /**
       * Creates a newly allocated byte array. Its size is the current
       * size of this output stream and the valid contents of the buffer
       * have been copied into it.
       *
       * @return  the current contents of this output stream, as a byte array.
       * @see     java.io.ByteArrayOutputStream#size()
       */
      toByteArray() {
        return me.copyOfUint8Array(this.buf, this.count);
      }
      /**
       * Returns the current size of the buffer.
       *
       * @return  the value of the <code>count</code> field, which is the number
       *          of valid bytes in this output stream.
       * @see     java.io.ByteArrayOutputStream#count
       */
      size() {
        return this.count;
      }
      toString(e) {
        return e ? typeof e == "string" ? this.toString_string(e) : this.toString_number(e) : this.toString_void();
      }
      /**
       * Converts the buffer's contents into a string decoding bytes using the
       * platform's default character set. The length of the new <tt>String</tt>
       * is a function of the character set, and hence may not be equal to the
       * size of the buffer.
       *
       * <p> This method always replaces malformed-input and unmappable-character
       * sequences with the default replacement string for the platform's
       * default character set. The {@linkplain java.nio.charset.CharsetDecoder}
       * class should be used when more control over the decoding process is
       * required.
       *
       * @return String decoded from the buffer's contents.
       * @since  JDK1.1
       */
      toString_void() {
        return new String(
          this.buf
          /*, 0, this.count*/
        ).toString();
      }
      /**
       * Converts the buffer's contents into a string by decoding the bytes using
       * the specified {@link java.nio.charset.Charset charsetName}. The length of
       * the new <tt>String</tt> is a function of the charset, and hence may not be
       * equal to the length of the byte array.
       *
       * <p> This method always replaces malformed-input and unmappable-character
       * sequences with this charset's default replacement string. The {@link
       * java.nio.charset.CharsetDecoder} class should be used when more control
       * over the decoding process is required.
       *
       * @param  charsetName  the name of a supported
       *              {@linkplain java.nio.charset.Charset </code>charset<code>}
       * @return String decoded from the buffer's contents.
       * @exception  UnsupportedEncodingException
       *             If the named charset is not supported
       * @since   JDK1.1
       */
      toString_string(e) {
        return new String(
          this.buf
          /*, 0, this.count, charsetName*/
        ).toString();
      }
      /**
       * Creates a newly allocated string. Its size is the current size of
       * the output stream and the valid contents of the buffer have been
       * copied into it. Each character <i>c</i> in the resulting string is
       * constructed from the corresponding element <i>b</i> in the byte
       * array such that:
       * <blockquote><pre>
       *     c == (char)(((hibyte &amp; 0xff) &lt;&lt; 8) | (b &amp; 0xff))
       * </pre></blockquote>
       *
       * @deprecated This method does not properly convert bytes into characters.
       * As of JDK&nbsp;1.1, the preferred way to do this is via the
       * <code>toString(String enc)</code> method, which takes an encoding-name
       * argument, or the <code>toString()</code> method, which uses the
       * platform's default character encoding.
       *
       * @param      hibyte    the high byte of each resulting Unicode character.
       * @return     the current contents of the output stream, as a string.
       * @see        java.io.ByteArrayOutputStream#size()
       * @see        java.io.ByteArrayOutputStream#toString(String)
       * @see        java.io.ByteArrayOutputStream#toString()
       */
      // @Deprecated
      toString_number(e) {
        return new String(
          this.buf
          /*, hibyte, 0, this.count*/
        ).toString();
      }
      /**
       * Closing a <tt>ByteArrayOutputStream</tt> has no effect. The methods in
       * this class can be called after the stream has been closed without
       * generating an <tt>IOException</tt>.
       * <p>
       *
       * @throws IOException
       */
      close() {
      }
    }
    var we;
    (function(u) {
      u[u.ALPHA = 0] = "ALPHA", u[u.LOWER = 1] = "LOWER", u[u.MIXED = 2] = "MIXED", u[u.PUNCT = 3] = "PUNCT", u[u.ALPHA_SHIFT = 4] = "ALPHA_SHIFT", u[u.PUNCT_SHIFT = 5] = "PUNCT_SHIFT";
    })(we || (we = {}));
    function Cr() {
      if (typeof window < "u")
        return window.BigInt || null;
      if (typeof gn < "u")
        return gn.BigInt || null;
      if (typeof self < "u")
        return self.BigInt || null;
      throw new Error("Can't search globals for BigInt!");
    }
    let Sn;
    function Bt(u) {
      if (typeof Sn > "u" && (Sn = Cr()), Sn === null)
        throw new Error("BigInt is not supported!");
      return Sn(u);
    }
    function si() {
      let u = [];
      u[0] = Bt(1);
      let e = Bt(900);
      u[1] = e;
      for (let t = 2; t < 16; t++)
        u[t] = u[t - 1] * e;
      return u;
    }
    class N {
      //   private DecodedBitStreamParser() {
      // }
      /**
       *
       * @param codewords
       * @param ecLevel
       *
       * @throws FormatException
       */
      static decode(e, t) {
        let n = new ge(""), r = k.ISO8859_1;
        n.enableDecoding(r);
        let i = 1, s = e[i++], o = new wr();
        for (; i < e[0]; ) {
          switch (s) {
            case N.TEXT_COMPACTION_MODE_LATCH:
              i = N.textCompaction(e, i, n);
              break;
            case N.BYTE_COMPACTION_MODE_LATCH:
            case N.BYTE_COMPACTION_MODE_LATCH_6:
              i = N.byteCompaction(s, e, r, i, n);
              break;
            case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
              n.append(
                /*(char)*/
                e[i++]
              );
              break;
            case N.NUMERIC_COMPACTION_MODE_LATCH:
              i = N.numericCompaction(e, i, n);
              break;
            case N.ECI_CHARSET:
              k.getCharacterSetECIByValue(e[i++]);
              break;
            case N.ECI_GENERAL_PURPOSE:
              i += 2;
              break;
            case N.ECI_USER_DEFINED:
              i++;
              break;
            case N.BEGIN_MACRO_PDF417_CONTROL_BLOCK:
              i = N.decodeMacroBlock(e, i, o);
              break;
            case N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD:
            case N.MACRO_PDF417_TERMINATOR:
              throw new U();
            default:
              i--, i = N.textCompaction(e, i, n);
              break;
          }
          if (i < e.length)
            s = e[i++];
          else
            throw U.getFormatInstance();
        }
        if (n.length() === 0)
          throw U.getFormatInstance();
        let a = new nn(null, n.toString(), null, t);
        return a.setOther(o), a;
      }
      /**
       *
       * @param int
       * @param param1
       * @param codewords
       * @param int
       * @param codeIndex
       * @param PDF417ResultMetadata
       * @param resultMetadata
       *
       * @throws FormatException
       */
      // @SuppressWarnings("deprecation")
      static decodeMacroBlock(e, t, n) {
        if (t + N.NUMBER_OF_SEQUENCE_CODEWORDS > e[0])
          throw U.getFormatInstance();
        let r = new Int32Array(N.NUMBER_OF_SEQUENCE_CODEWORDS);
        for (let o = 0; o < N.NUMBER_OF_SEQUENCE_CODEWORDS; o++, t++)
          r[o] = e[t];
        n.setSegmentIndex(j.parseInt(N.decodeBase900toBase10(r, N.NUMBER_OF_SEQUENCE_CODEWORDS)));
        let i = new ge();
        t = N.textCompaction(e, t, i), n.setFileId(i.toString());
        let s = -1;
        for (e[t] === N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD && (s = t + 1); t < e[0]; )
          switch (e[t]) {
            case N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD:
              switch (t++, e[t]) {
                case N.MACRO_PDF417_OPTIONAL_FIELD_FILE_NAME:
                  let o = new ge();
                  t = N.textCompaction(e, t + 1, o), n.setFileName(o.toString());
                  break;
                case N.MACRO_PDF417_OPTIONAL_FIELD_SENDER:
                  let a = new ge();
                  t = N.textCompaction(e, t + 1, a), n.setSender(a.toString());
                  break;
                case N.MACRO_PDF417_OPTIONAL_FIELD_ADDRESSEE:
                  let l = new ge();
                  t = N.textCompaction(e, t + 1, l), n.setAddressee(l.toString());
                  break;
                case N.MACRO_PDF417_OPTIONAL_FIELD_SEGMENT_COUNT:
                  let c = new ge();
                  t = N.numericCompaction(e, t + 1, c), n.setSegmentCount(j.parseInt(c.toString()));
                  break;
                case N.MACRO_PDF417_OPTIONAL_FIELD_TIME_STAMP:
                  let f = new ge();
                  t = N.numericCompaction(e, t + 1, f), n.setTimestamp(Ar.parseLong(f.toString()));
                  break;
                case N.MACRO_PDF417_OPTIONAL_FIELD_CHECKSUM:
                  let d = new ge();
                  t = N.numericCompaction(e, t + 1, d), n.setChecksum(j.parseInt(d.toString()));
                  break;
                case N.MACRO_PDF417_OPTIONAL_FIELD_FILE_SIZE:
                  let C = new ge();
                  t = N.numericCompaction(e, t + 1, C), n.setFileSize(Ar.parseLong(C.toString()));
                  break;
                default:
                  throw U.getFormatInstance();
              }
              break;
            case N.MACRO_PDF417_TERMINATOR:
              t++, n.setLastSegment(!0);
              break;
            default:
              throw U.getFormatInstance();
          }
        if (s !== -1) {
          let o = t - s;
          n.isLastSegment() && o--, n.setOptionalData(me.copyOfRange(e, s, s + o));
        }
        return t;
      }
      /**
       * Text Compaction mode (see 5.4.1.5) permits all printable ASCII characters to be
       * encoded, i.e. values 32 - 126 inclusive in accordance with ISO/IEC 646 (IRV), as
       * well as selected control characters.
       *
       * @param codewords The array of codewords (data + error)
       * @param codeIndex The current index into the codeword array.
       * @param result    The decoded data is appended to the result.
       * @return The next index into the codeword array.
       */
      static textCompaction(e, t, n) {
        let r = new Int32Array((e[0] - t) * 2), i = new Int32Array((e[0] - t) * 2), s = 0, o = !1;
        for (; t < e[0] && !o; ) {
          let a = e[t++];
          if (a < N.TEXT_COMPACTION_MODE_LATCH)
            r[s] = a / 30, r[s + 1] = a % 30, s += 2;
          else
            switch (a) {
              case N.TEXT_COMPACTION_MODE_LATCH:
                r[s++] = N.TEXT_COMPACTION_MODE_LATCH;
                break;
              case N.BYTE_COMPACTION_MODE_LATCH:
              case N.BYTE_COMPACTION_MODE_LATCH_6:
              case N.NUMERIC_COMPACTION_MODE_LATCH:
              case N.BEGIN_MACRO_PDF417_CONTROL_BLOCK:
              case N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD:
              case N.MACRO_PDF417_TERMINATOR:
                t--, o = !0;
                break;
              case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
                r[s] = N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE, a = e[t++], i[s] = a, s++;
                break;
            }
        }
        return N.decodeTextCompaction(r, i, s, n), t;
      }
      /**
       * The Text Compaction mode includes all the printable ASCII characters
       * (i.e. values from 32 to 126) and three ASCII control characters: HT or tab
       * (9: e), LF or line feed (10: e), and CR or carriage
       * return (13: e). The Text Compaction mode also includes various latch
       * and shift characters which are used exclusively within the mode. The Text
       * Compaction mode encodes up to 2 characters per codeword. The compaction rules
       * for converting data into PDF417 codewords are defined in 5.4.2.2. The sub-mode
       * switches are defined in 5.4.2.3.
       *
       * @param textCompactionData The text compaction data.
       * @param byteCompactionData The byte compaction data if there
       *                           was a mode shift.
       * @param length             The size of the text compaction and byte compaction data.
       * @param result             The decoded data is appended to the result.
       */
      static decodeTextCompaction(e, t, n, r) {
        let i = we.ALPHA, s = we.ALPHA, o = 0;
        for (; o < n; ) {
          let a = e[o], l = (
            /*char*/
            ""
          );
          switch (i) {
            case we.ALPHA:
              if (a < 26)
                l = /*(char)('A' + subModeCh) */
                String.fromCharCode(65 + a);
              else
                switch (a) {
                  case 26:
                    l = " ";
                    break;
                  case N.LL:
                    i = we.LOWER;
                    break;
                  case N.ML:
                    i = we.MIXED;
                    break;
                  case N.PS:
                    s = i, i = we.PUNCT_SHIFT;
                    break;
                  case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
                    r.append(
                      /*(char)*/
                      t[o]
                    );
                    break;
                  case N.TEXT_COMPACTION_MODE_LATCH:
                    i = we.ALPHA;
                    break;
                }
              break;
            case we.LOWER:
              if (a < 26)
                l = /*(char)('a' + subModeCh)*/
                String.fromCharCode(97 + a);
              else
                switch (a) {
                  case 26:
                    l = " ";
                    break;
                  case N.AS:
                    s = i, i = we.ALPHA_SHIFT;
                    break;
                  case N.ML:
                    i = we.MIXED;
                    break;
                  case N.PS:
                    s = i, i = we.PUNCT_SHIFT;
                    break;
                  case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
                    r.append(
                      /*(char)*/
                      t[o]
                    );
                    break;
                  case N.TEXT_COMPACTION_MODE_LATCH:
                    i = we.ALPHA;
                    break;
                }
              break;
            case we.MIXED:
              if (a < N.PL)
                l = N.MIXED_CHARS[a];
              else
                switch (a) {
                  case N.PL:
                    i = we.PUNCT;
                    break;
                  case 26:
                    l = " ";
                    break;
                  case N.LL:
                    i = we.LOWER;
                    break;
                  case N.AL:
                    i = we.ALPHA;
                    break;
                  case N.PS:
                    s = i, i = we.PUNCT_SHIFT;
                    break;
                  case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
                    r.append(
                      /*(char)*/
                      t[o]
                    );
                    break;
                  case N.TEXT_COMPACTION_MODE_LATCH:
                    i = we.ALPHA;
                    break;
                }
              break;
            case we.PUNCT:
              if (a < N.PAL)
                l = N.PUNCT_CHARS[a];
              else
                switch (a) {
                  case N.PAL:
                    i = we.ALPHA;
                    break;
                  case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
                    r.append(
                      /*(char)*/
                      t[o]
                    );
                    break;
                  case N.TEXT_COMPACTION_MODE_LATCH:
                    i = we.ALPHA;
                    break;
                }
              break;
            case we.ALPHA_SHIFT:
              if (i = s, a < 26)
                l = /*(char)('A' + subModeCh)*/
                String.fromCharCode(65 + a);
              else
                switch (a) {
                  case 26:
                    l = " ";
                    break;
                  case N.TEXT_COMPACTION_MODE_LATCH:
                    i = we.ALPHA;
                    break;
                }
              break;
            case we.PUNCT_SHIFT:
              if (i = s, a < N.PAL)
                l = N.PUNCT_CHARS[a];
              else
                switch (a) {
                  case N.PAL:
                    i = we.ALPHA;
                    break;
                  case N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE:
                    r.append(
                      /*(char)*/
                      t[o]
                    );
                    break;
                  case N.TEXT_COMPACTION_MODE_LATCH:
                    i = we.ALPHA;
                    break;
                }
              break;
          }
          l !== "" && r.append(l), o++;
        }
      }
      /**
       * Byte Compaction mode (see 5.4.3) permits all 256 possible 8-bit byte values to be encoded.
       * This includes all ASCII characters value 0 to 127 inclusive and provides for international
       * character set support.
       *
       * @param mode      The byte compaction mode i.e. 901 or 924
       * @param codewords The array of codewords (data + error)
       * @param encoding  Currently active character encoding
       * @param codeIndex The current index into the codeword array.
       * @param result    The decoded data is appended to the result.
       * @return The next index into the codeword array.
       */
      static byteCompaction(e, t, n, r, i) {
        let s = new ii(), o = 0, a = (
          /*long*/
          0
        ), l = !1;
        switch (e) {
          case N.BYTE_COMPACTION_MODE_LATCH:
            let c = new Int32Array(6), f = t[r++];
            for (; r < t[0] && !l; )
              switch (c[o++] = f, a = 900 * a + f, f = t[r++], f) {
                case N.TEXT_COMPACTION_MODE_LATCH:
                case N.BYTE_COMPACTION_MODE_LATCH:
                case N.NUMERIC_COMPACTION_MODE_LATCH:
                case N.BYTE_COMPACTION_MODE_LATCH_6:
                case N.BEGIN_MACRO_PDF417_CONTROL_BLOCK:
                case N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD:
                case N.MACRO_PDF417_TERMINATOR:
                  r--, l = !0;
                  break;
                default:
                  if (o % 5 === 0 && o > 0) {
                    for (let d = 0; d < 6; ++d)
                      s.write(
                        /*(byte)*/
                        Number(Bt(a) >> Bt(8 * (5 - d)))
                      );
                    a = 0, o = 0;
                  }
                  break;
              }
            r === t[0] && f < N.TEXT_COMPACTION_MODE_LATCH && (c[o++] = f);
            for (let d = 0; d < o; d++)
              s.write(
                /*(byte)*/
                c[d]
              );
            break;
          case N.BYTE_COMPACTION_MODE_LATCH_6:
            for (; r < t[0] && !l; ) {
              let d = t[r++];
              if (d < N.TEXT_COMPACTION_MODE_LATCH)
                o++, a = 900 * a + d;
              else
                switch (d) {
                  case N.TEXT_COMPACTION_MODE_LATCH:
                  case N.BYTE_COMPACTION_MODE_LATCH:
                  case N.NUMERIC_COMPACTION_MODE_LATCH:
                  case N.BYTE_COMPACTION_MODE_LATCH_6:
                  case N.BEGIN_MACRO_PDF417_CONTROL_BLOCK:
                  case N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD:
                  case N.MACRO_PDF417_TERMINATOR:
                    r--, l = !0;
                    break;
                }
              if (o % 5 === 0 && o > 0) {
                for (let C = 0; C < 6; ++C)
                  s.write(
                    /*(byte)*/
                    Number(Bt(a) >> Bt(8 * (5 - C)))
                  );
                a = 0, o = 0;
              }
            }
            break;
        }
        return i.append(Je.decode(s.toByteArray(), n)), r;
      }
      /**
       * Numeric Compaction mode (see 5.4.4) permits efficient encoding of numeric data strings.
       *
       * @param codewords The array of codewords (data + error)
       * @param codeIndex The current index into the codeword array.
       * @param result    The decoded data is appended to the result.
       * @return The next index into the codeword array.
       *
       * @throws FormatException
       */
      static numericCompaction(e, t, n) {
        let r = 0, i = !1, s = new Int32Array(N.MAX_NUMERIC_CODEWORDS);
        for (; t < e[0] && !i; ) {
          let o = e[t++];
          if (t === e[0] && (i = !0), o < N.TEXT_COMPACTION_MODE_LATCH)
            s[r] = o, r++;
          else
            switch (o) {
              case N.TEXT_COMPACTION_MODE_LATCH:
              case N.BYTE_COMPACTION_MODE_LATCH:
              case N.BYTE_COMPACTION_MODE_LATCH_6:
              case N.BEGIN_MACRO_PDF417_CONTROL_BLOCK:
              case N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD:
              case N.MACRO_PDF417_TERMINATOR:
                t--, i = !0;
                break;
            }
          (r % N.MAX_NUMERIC_CODEWORDS === 0 || o === N.NUMERIC_COMPACTION_MODE_LATCH || i) && r > 0 && (n.append(N.decodeBase900toBase10(s, r)), r = 0);
        }
        return t;
      }
      /**
       * Convert a list of Numeric Compacted codewords from Base 900 to Base 10.
       *
       * @param codewords The array of codewords
       * @param count     The number of codewords
       * @return The decoded string representing the Numeric data.
       *
       * EXAMPLE
       * Encode the fifteen digit numeric string 000213298174000
       * Prefix the numeric string with a 1 and set the initial value of
       * t = 1 000 213 298 174 000
       * Calculate codeword 0
       * d0 = 1 000 213 298 174 000 mod 900 = 200
       *
       * t = 1 000 213 298 174 000 div 900 = 1 111 348 109 082
       * Calculate codeword 1
       * d1 = 1 111 348 109 082 mod 900 = 282
       *
       * t = 1 111 348 109 082 div 900 = 1 234 831 232
       * Calculate codeword 2
       * d2 = 1 234 831 232 mod 900 = 632
       *
       * t = 1 234 831 232 div 900 = 1 372 034
       * Calculate codeword 3
       * d3 = 1 372 034 mod 900 = 434
       *
       * t = 1 372 034 div 900 = 1 524
       * Calculate codeword 4
       * d4 = 1 524 mod 900 = 624
       *
       * t = 1 524 div 900 = 1
       * Calculate codeword 5
       * d5 = 1 mod 900 = 1
       * t = 1 div 900 = 0
       * Codeword sequence is: 1, 624, 434, 632, 282, 200
       *
       * Decode the above codewords involves
       *   1 x 900 power of 5 + 624 x 900 power of 4 + 434 x 900 power of 3 +
       * 632 x 900 power of 2 + 282 x 900 power of 1 + 200 x 900 power of 0 = 1000213298174000
       *
       * Remove leading 1 =>  Result is 000213298174000
       *
       * @throws FormatException
       */
      static decodeBase900toBase10(e, t) {
        let n = Bt(0);
        for (let i = 0; i < t; i++)
          n += N.EXP900[t - i - 1] * Bt(e[i]);
        let r = n.toString();
        if (r.charAt(0) !== "1")
          throw new U();
        return r.substring(1);
      }
    }
    N.TEXT_COMPACTION_MODE_LATCH = 900, N.BYTE_COMPACTION_MODE_LATCH = 901, N.NUMERIC_COMPACTION_MODE_LATCH = 902, N.BYTE_COMPACTION_MODE_LATCH_6 = 924, N.ECI_USER_DEFINED = 925, N.ECI_GENERAL_PURPOSE = 926, N.ECI_CHARSET = 927, N.BEGIN_MACRO_PDF417_CONTROL_BLOCK = 928, N.BEGIN_MACRO_PDF417_OPTIONAL_FIELD = 923, N.MACRO_PDF417_TERMINATOR = 922, N.MODE_SHIFT_TO_BYTE_COMPACTION_MODE = 913, N.MAX_NUMERIC_CODEWORDS = 15, N.MACRO_PDF417_OPTIONAL_FIELD_FILE_NAME = 0, N.MACRO_PDF417_OPTIONAL_FIELD_SEGMENT_COUNT = 1, N.MACRO_PDF417_OPTIONAL_FIELD_TIME_STAMP = 2, N.MACRO_PDF417_OPTIONAL_FIELD_SENDER = 3, N.MACRO_PDF417_OPTIONAL_FIELD_ADDRESSEE = 4, N.MACRO_PDF417_OPTIONAL_FIELD_FILE_SIZE = 5, N.MACRO_PDF417_OPTIONAL_FIELD_CHECKSUM = 6, N.PL = 25, N.LL = 27, N.AS = 27, N.ML = 28, N.AL = 28, N.PS = 29, N.PAL = 29, N.PUNCT_CHARS = `;<>@[\\]_\`~!\r	,:
-.$/"|*()?{}'`, N.MIXED_CHARS = "0123456789&\r	,:#-.$/+%*=^", N.EXP900 = Cr() ? si() : [], N.NUMBER_OF_SEQUENCE_CODEWORDS = 2;
    class te {
      constructor() {
      }
      /**
       * @TODO don't pass in minCodewordWidth and maxCodewordWidth, pass in barcode columns for start and stop pattern
       *
       * columns. That way width can be deducted from the pattern column.
       * This approach also allows to detect more details about the barcode, e.g. if a bar type (white or black) is wider
       * than it should be. This can happen if the scanner used a bad blackpoint.
       *
       * @param BitMatrix
       * @param image
       * @param ResultPoint
       * @param imageTopLeft
       * @param ResultPoint
       * @param imageBottomLeft
       * @param ResultPoint
       * @param imageTopRight
       * @param ResultPoint
       * @param imageBottomRight
       * @param int
       * @param minCodewordWidth
       * @param int
       * @param maxCodewordWidth
       *
       * @throws NotFoundException
       * @throws FormatException
       * @throws ChecksumException
       */
      static decode(e, t, n, r, i, s, o) {
        let a = new Ut(e, t, n, r, i), l = null, c = null, f;
        for (let p = !0; ; p = !1) {
          if (t != null && (l = te.getRowIndicatorColumn(e, a, t, !0, s, o)), r != null && (c = te.getRowIndicatorColumn(e, a, r, !1, s, o)), f = te.merge(l, c), f == null)
            throw O.getNotFoundInstance();
          let b = f.getBoundingBox();
          if (p && b != null && (b.getMinY() < a.getMinY() || b.getMaxY() > a.getMaxY()))
            a = b;
          else
            break;
        }
        f.setBoundingBox(a);
        let d = f.getBarcodeColumnCount() + 1;
        f.setDetectionResultColumn(0, l), f.setDetectionResultColumn(d, c);
        let C = l != null;
        for (let p = 1; p <= d; p++) {
          let b = C ? p : d - p;
          if (f.getDetectionResultColumn(b) !== /* null */
          void 0)
            continue;
          let y;
          b === 0 || b === d ? y = new gr(a, b === 0) : y = new cn(a), f.setDetectionResultColumn(b, y);
          let _ = -1, P = _;
          for (let F = a.getMinY(); F <= a.getMaxY(); F++) {
            if (_ = te.getStartColumn(f, b, F, C), _ < 0 || _ > a.getMaxX()) {
              if (P === -1)
                continue;
              _ = P;
            }
            let L = te.detectCodeword(e, a.getMinX(), a.getMaxX(), C, _, F, s, o);
            L != null && (y.setCodeword(F, L), P = _, s = Math.min(s, L.getWidth()), o = Math.max(o, L.getWidth()));
          }
        }
        return te.createDecoderResult(f);
      }
      /**
       *
       * @param leftRowIndicatorColumn
       * @param rightRowIndicatorColumn
       *
       * @throws NotFoundException
       */
      static merge(e, t) {
        if (e == null && t == null)
          return null;
        let n = te.getBarcodeMetadata(e, t);
        if (n == null)
          return null;
        let r = Ut.merge(te.adjustBoundingBox(e), te.adjustBoundingBox(t));
        return new un(n, r);
      }
      /**
       *
       * @param rowIndicatorColumn
       *
       * @throws NotFoundException
       */
      static adjustBoundingBox(e) {
        if (e == null)
          return null;
        let t = e.getRowHeights();
        if (t == null)
          return null;
        let n = te.getMax(t), r = 0;
        for (let o of t)
          if (r += n - o, o > 0)
            break;
        let i = e.getCodewords();
        for (let o = 0; r > 0 && i[o] == null; o++)
          r--;
        let s = 0;
        for (let o = t.length - 1; o >= 0 && (s += n - t[o], !(t[o] > 0)); o--)
          ;
        for (let o = i.length - 1; s > 0 && i[o] == null; o--)
          s--;
        return e.getBoundingBox().addMissingRows(r, s, e.isLeft());
      }
      static getMax(e) {
        let t = -1;
        for (let n of e)
          t = Math.max(t, n);
        return t;
      }
      static getBarcodeMetadata(e, t) {
        let n;
        if (e == null || (n = e.getBarcodeMetadata()) == null)
          return t == null ? null : t.getBarcodeMetadata();
        let r;
        return t == null || (r = t.getBarcodeMetadata()) == null ? n : n.getColumnCount() !== r.getColumnCount() && n.getErrorCorrectionLevel() !== r.getErrorCorrectionLevel() && n.getRowCount() !== r.getRowCount() ? null : n;
      }
      static getRowIndicatorColumn(e, t, n, r, i, s) {
        let o = new gr(t, r);
        for (let a = 0; a < 2; a++) {
          let l = a === 0 ? 1 : -1, c = Math.trunc(Math.trunc(n.getX()));
          for (let f = Math.trunc(Math.trunc(n.getY())); f <= t.getMaxY() && f >= t.getMinY(); f += l) {
            let d = te.detectCodeword(e, 0, e.getWidth(), r, c, f, i, s);
            d != null && (o.setCodeword(f, d), r ? c = d.getStartX() : c = d.getEndX());
          }
        }
        return o;
      }
      /**
       *
       * @param detectionResult
       * @param BarcodeValue
       * @param param2
       * @param param3
       * @param barcodeMatrix
       *
       * @throws NotFoundException
       */
      static adjustCodewordCount(e, t) {
        let n = t[0][1], r = n.getValue(), i = e.getBarcodeColumnCount() * e.getBarcodeRowCount() - te.getNumberOfECCodeWords(e.getBarcodeECLevel());
        if (r.length === 0) {
          if (i < 1 || i > K.MAX_CODEWORDS_IN_BARCODE)
            throw O.getNotFoundInstance();
          n.setValue(i);
        } else r[0] !== i && n.setValue(i);
      }
      /**
       *
       * @param detectionResult
       *
       * @throws FormatException
       * @throws ChecksumException
       * @throws NotFoundException
       */
      static createDecoderResult(e) {
        let t = te.createBarcodeMatrix(e);
        te.adjustCodewordCount(e, t);
        let n = new Array(), r = new Int32Array(e.getBarcodeRowCount() * e.getBarcodeColumnCount()), i = (
          /*List<int[]>*/
          []
        ), s = (
          /*Collection<Integer>*/
          new Array()
        );
        for (let a = 0; a < e.getBarcodeRowCount(); a++)
          for (let l = 0; l < e.getBarcodeColumnCount(); l++) {
            let c = t[a][l + 1].getValue(), f = a * e.getBarcodeColumnCount() + l;
            c.length === 0 ? n.push(f) : c.length === 1 ? r[f] = c[0] : (s.push(f), i.push(c));
          }
        let o = new Array(i.length);
        for (let a = 0; a < o.length; a++)
          o[a] = i[a];
        return te.createDecoderResultFromAmbiguousValues(e.getBarcodeECLevel(), r, K.toIntArray(n), K.toIntArray(s), o);
      }
      /**
       * This method deals with the fact, that the decoding process doesn't always yield a single most likely value. The
       * current error correction implementation doesn't deal with erasures very well, so it's better to provide a value
       * for these ambiguous codewords instead of treating it as an erasure. The problem is that we don't know which of
       * the ambiguous values to choose. We try decode using the first value, and if that fails, we use another of the
       * ambiguous values and try to decode again. This usually only happens on very hard to read and decode barcodes,
       * so decoding the normal barcodes is not affected by this.
       *
       * @param erasureArray contains the indexes of erasures
       * @param ambiguousIndexes array with the indexes that have more than one most likely value
       * @param ambiguousIndexValues two dimensional array that contains the ambiguous values. The first dimension must
       * be the same length as the ambiguousIndexes array
       *
       * @throws FormatException
       * @throws ChecksumException
       */
      static createDecoderResultFromAmbiguousValues(e, t, n, r, i) {
        let s = new Int32Array(r.length), o = 100;
        for (; o-- > 0; ) {
          for (let a = 0; a < s.length; a++)
            t[r[a]] = i[a][s[a]];
          try {
            return te.decodeCodewords(t, e, n);
          } catch (a) {
            if (!(a instanceof J))
              throw a;
          }
          if (s.length === 0)
            throw J.getChecksumInstance();
          for (let a = 0; a < s.length; a++)
            if (s[a] < i[a].length - 1) {
              s[a]++;
              break;
            } else if (s[a] = 0, a === s.length - 1)
              throw J.getChecksumInstance();
        }
        throw J.getChecksumInstance();
      }
      static createBarcodeMatrix(e) {
        let t = Array.from({ length: e.getBarcodeRowCount() }, () => new Array(e.getBarcodeColumnCount() + 2));
        for (let r = 0; r < t.length; r++)
          for (let i = 0; i < t[r].length; i++)
            t[r][i] = new hn();
        let n = 0;
        for (let r of e.getDetectionResultColumns()) {
          if (r != null) {
            for (let i of r.getCodewords())
              if (i != null) {
                let s = i.getRowNumber();
                if (s >= 0) {
                  if (s >= t.length)
                    continue;
                  t[s][n].setValue(i.getValue());
                }
              }
          }
          n++;
        }
        return t;
      }
      static isValidBarcodeColumn(e, t) {
        return t >= 0 && t <= e.getBarcodeColumnCount() + 1;
      }
      static getStartColumn(e, t, n, r) {
        let i = r ? 1 : -1, s = null;
        if (te.isValidBarcodeColumn(e, t - i) && (s = e.getDetectionResultColumn(t - i).getCodeword(n)), s != null)
          return r ? s.getEndX() : s.getStartX();
        if (s = e.getDetectionResultColumn(t).getCodewordNearby(n), s != null)
          return r ? s.getStartX() : s.getEndX();
        if (te.isValidBarcodeColumn(e, t - i) && (s = e.getDetectionResultColumn(t - i).getCodewordNearby(n)), s != null)
          return r ? s.getEndX() : s.getStartX();
        let o = 0;
        for (; te.isValidBarcodeColumn(e, t - i); ) {
          t -= i;
          for (let a of e.getDetectionResultColumn(t).getCodewords())
            if (a != null)
              return (r ? a.getEndX() : a.getStartX()) + i * o * (a.getEndX() - a.getStartX());
          o++;
        }
        return r ? e.getBoundingBox().getMinX() : e.getBoundingBox().getMaxX();
      }
      static detectCodeword(e, t, n, r, i, s, o, a) {
        i = te.adjustCodewordStartColumn(e, t, n, r, i, s);
        let l = te.getModuleBitCount(e, t, n, r, i, s);
        if (l == null)
          return null;
        let c, f = oe.sum(l);
        if (r)
          c = i + f;
        else {
          for (let p = 0; p < l.length / 2; p++) {
            let b = l[p];
            l[p] = l[l.length - 1 - p], l[l.length - 1 - p] = b;
          }
          c = i, i = c - f;
        }
        if (!te.checkCodewordSkew(f, o, a))
          return null;
        let d = nt.getDecodedValue(l), C = K.getCodeword(d);
        return C === -1 ? null : new fn(i, c, te.getCodewordBucketNumber(d), C);
      }
      static getModuleBitCount(e, t, n, r, i, s) {
        let o = i, a = new Int32Array(8), l = 0, c = r ? 1 : -1, f = r;
        for (; (r ? o < n : o >= t) && l < a.length; )
          e.get(o, s) === f ? (a[l]++, o += c) : (l++, f = !f);
        return l === a.length || o === (r ? n : t) && l === a.length - 1 ? a : null;
      }
      static getNumberOfECCodeWords(e) {
        return 2 << e;
      }
      static adjustCodewordStartColumn(e, t, n, r, i, s) {
        let o = i, a = r ? -1 : 1;
        for (let l = 0; l < 2; l++) {
          for (; (r ? o >= t : o < n) && r === e.get(o, s); ) {
            if (Math.abs(i - o) > te.CODEWORD_SKEW_SIZE)
              return i;
            o += a;
          }
          a = -a, r = !r;
        }
        return o;
      }
      static checkCodewordSkew(e, t, n) {
        return t - te.CODEWORD_SKEW_SIZE <= e && e <= n + te.CODEWORD_SKEW_SIZE;
      }
      /**
       * @throws FormatException,
       * @throws ChecksumException
       */
      static decodeCodewords(e, t, n) {
        if (e.length === 0)
          throw U.getFormatInstance();
        let r = 1 << t + 1, i = te.correctErrors(e, n, r);
        te.verifyCodewordCount(e, r);
        let s = N.decode(e, "" + t);
        return s.setErrorsCorrected(i), s.setErasures(n.length), s;
      }
      /**
       * <p>Given data and error-correction codewords received, possibly corrupted by errors, attempts to
       * correct the errors in-place.</p>
       *
       * @param codewords   data and error correction codewords
       * @param erasures positions of any known erasures
       * @param numECCodewords number of error correction codewords that are available in codewords
       * @throws ChecksumException if error correction fails
       */
      static correctErrors(e, t, n) {
        if (t != null && t.length > n / 2 + te.MAX_ERRORS || n < 0 || n > te.MAX_EC_CODEWORDS)
          throw J.getChecksumInstance();
        return te.errorCorrection.decode(e, n, t);
      }
      /**
       * Verify that all is OK with the codeword array.
       * @throws FormatException
       */
      static verifyCodewordCount(e, t) {
        if (e.length < 4)
          throw U.getFormatInstance();
        let n = e[0];
        if (n > e.length)
          throw U.getFormatInstance();
        if (n === 0)
          if (t < e.length)
            e[0] = e.length - t;
          else
            throw U.getFormatInstance();
      }
      static getBitCountForCodeword(e) {
        let t = new Int32Array(8), n = 0, r = t.length - 1;
        for (; !((e & 1) !== n && (n = e & 1, r--, r < 0)); )
          t[r]++, e >>= 1;
        return t;
      }
      static getCodewordBucketNumber(e) {
        return e instanceof Int32Array ? this.getCodewordBucketNumber_Int32Array(e) : this.getCodewordBucketNumber_number(e);
      }
      static getCodewordBucketNumber_number(e) {
        return te.getCodewordBucketNumber(te.getBitCountForCodeword(e));
      }
      static getCodewordBucketNumber_Int32Array(e) {
        return (e[0] - e[2] + e[4] - e[6] + 9) % 9;
      }
      static toString(e) {
        let t = new ln();
        for (let n = 0; n < e.length; n++) {
          t.format("Row %2d: ", n);
          for (let r = 0; r < e[n].length; r++) {
            let i = e[n][r];
            i.getValue().length === 0 ? t.format("        ", null) : t.format("%4d(%2d)", i.getValue()[0], i.getConfidence(i.getValue()[0]));
          }
          t.format("%n");
        }
        return t.toString();
      }
    }
    te.CODEWORD_SKEW_SIZE = 2, te.MAX_ERRORS = 3, te.MAX_EC_CODEWORDS = 512, te.errorCorrection = new xr();
    class Xe {
      // private static /*final Result[]*/ EMPTY_RESULT_ARRAY: Result[] = new Result([0]);
      /**
       * Locates and decodes a PDF417 code in an image.
       *
       * @return a String representing the content encoded by the PDF417 code
       * @throws NotFoundException if a PDF417 code cannot be found,
       * @throws FormatException if a PDF417 cannot be decoded
       * @throws ChecksumException
       */
      // @Override
      decode(e, t = null) {
        let n = Xe.decode(e, t, !1);
        if (n == null || n.length === 0 || n[0] == null)
          throw O.getNotFoundInstance();
        return n[0];
      }
      /**
       *
       * @param BinaryBitmap
       * @param image
       * @throws NotFoundException
       */
      //   @Override
      decodeMultiple(e, t = null) {
        try {
          return Xe.decode(e, t, !0);
        } catch (n) {
          throw n instanceof U || n instanceof J ? O.getNotFoundInstance() : n;
        }
      }
      /**
       *
       * @param image
       * @param hints
       * @param multiple
       *
       * @throws NotFoundException
       * @throws FormatExceptionß
       * @throws ChecksumException
       */
      static decode(e, t, n) {
        const r = new Array(), i = ee.detectMultiple(e, t, n);
        for (const s of i.getPoints()) {
          const o = te.decode(i.getBits(), s[4], s[5], s[6], s[7], Xe.getMinCodewordWidth(s), Xe.getMaxCodewordWidth(s)), a = new je(o.getText(), o.getRawBytes(), void 0, s, z.PDF_417);
          a.putMetadata(Ue.ERROR_CORRECTION_LEVEL, o.getECLevel());
          const l = o.getOther();
          l != null && a.putMetadata(Ue.PDF417_EXTRA_METADATA, l), r.push(a);
        }
        return r.map((s) => s);
      }
      static getMaxWidth(e, t) {
        return e == null || t == null ? 0 : Math.trunc(Math.abs(e.getX() - t.getX()));
      }
      static getMinWidth(e, t) {
        return e == null || t == null ? j.MAX_VALUE : Math.trunc(Math.abs(e.getX() - t.getX()));
      }
      static getMaxCodewordWidth(e) {
        return Math.floor(Math.max(Math.max(Xe.getMaxWidth(e[0], e[4]), Xe.getMaxWidth(e[6], e[2]) * K.MODULES_IN_CODEWORD / K.MODULES_IN_STOP_PATTERN), Math.max(Xe.getMaxWidth(e[1], e[5]), Xe.getMaxWidth(e[7], e[3]) * K.MODULES_IN_CODEWORD / K.MODULES_IN_STOP_PATTERN)));
      }
      static getMinCodewordWidth(e) {
        return Math.floor(Math.min(Math.min(Xe.getMinWidth(e[0], e[4]), Xe.getMinWidth(e[6], e[2]) * K.MODULES_IN_CODEWORD / K.MODULES_IN_STOP_PATTERN), Math.min(Xe.getMinWidth(e[1], e[5]), Xe.getMinWidth(e[7], e[3]) * K.MODULES_IN_CODEWORD / K.MODULES_IN_STOP_PATTERN)));
      }
      // @Override
      reset() {
      }
    }
    class Tn extends E {
    }
    Tn.kind = "ReaderException";
    class mr {
      /**
       * Creates an instance of this class
       * 
       * @param {Boolean} verbose if 'true' logs will be dumped to console, otherwise hidden.
       * @param hints The hints to use, clearing the previous state.
       */
      constructor(e, t) {
        this.verbose = e === !0, t && this.setHints(t);
      }
      /**
       * This version of decode honors the intent of Reader.decode(BinaryBitmap) in that it
       * passes null as a hint to the decoders. However, that makes it inefficient to call repeatedly.
       * Use setHints() followed by decodeWithState() for continuous scan applications.
       *
       * @param image The pixel data to decode
       * @return The contents of the image
       *
       * @throws NotFoundException Any errors which occurred
       */
      /*@Override*/
      // public decode(image: BinaryBitmap): Result {
      //   setHints(null)
      //   return decodeInternal(image)
      // }
      /**
       * Decode an image using the hints provided. Does not honor existing state.
       *
       * @param image The pixel data to decode
       * @param hints The hints to use, clearing the previous state.
       * @return The contents of the image
       *
       * @throws NotFoundException Any errors which occurred
       */
      /*@Override*/
      decode(e, t) {
        return t && this.setHints(t), this.decodeInternal(e);
      }
      /**
       * Decode an image using the state set up by calling setHints() previously. Continuous scan
       * clients will get a <b>large</b> speed increase by using this instead of decode().
       *
       * @param image The pixel data to decode
       * @return The contents of the image
       *
       * @throws NotFoundException Any errors which occurred
       */
      decodeWithState(e) {
        return (this.readers === null || this.readers === void 0) && this.setHints(null), this.decodeInternal(e);
      }
      /**
       * This method adds state to the MultiFormatReader. By setting the hints once, subsequent calls
       * to decodeWithState(image) can reuse the same set of readers without reallocating memory. This
       * is important for performance in continuous scan clients.
       *
       * @param hints The set of hints to use for subsequent calls to decode(image)
       */
      setHints(e) {
        this.hints = e;
        const t = !m(e) && e.get(xe.TRY_HARDER) === !0, n = m(e) ? null : e.get(xe.POSSIBLE_FORMATS), r = new Array();
        if (!m(n)) {
          const i = n.some((s) => s === z.UPC_A || s === z.UPC_E || s === z.EAN_13 || s === z.EAN_8 || s === z.CODABAR || s === z.CODE_39 || s === z.CODE_93 || s === z.CODE_128 || s === z.ITF || s === z.RSS_14 || s === z.RSS_EXPANDED);
          i && !t && r.push(new Kt(e, this.verbose)), n.includes(z.QR_CODE) && r.push(new Mt()), n.includes(z.DATA_MATRIX) && r.push(new Dt()), n.includes(z.AZTEC) && r.push(new mn()), n.includes(z.PDF_417) && r.push(new Xe()), i && t && r.push(new Kt(e, this.verbose));
        }
        r.length === 0 && (t || r.push(new Kt(e, this.verbose)), r.push(new Mt()), r.push(new Dt()), r.push(new mn()), r.push(new Xe()), t && r.push(new Kt(e, this.verbose))), this.readers = r;
      }
      /*@Override*/
      reset() {
        if (this.readers !== null)
          for (const e of this.readers)
            e.reset();
      }
      /**
       * @throws NotFoundException
       */
      decodeInternal(e) {
        if (this.readers === null)
          throw new Tn("No readers where selected, nothing can be read.");
        for (const t of this.readers)
          try {
            return t.decode(e, this.hints);
          } catch (n) {
            if (n instanceof Tn)
              continue;
          }
        throw new O("No MultiFormat Readers were able to detect the code.");
      }
    }
    class oi extends Lt {
      constructor(e = null, t = 500) {
        const n = new mr();
        n.setHints(e), super(n, t);
      }
      /**
       * Overwrite decodeBitmap to call decodeWithState, which will pay
       * attention to the hints set in the constructor function
       */
      decodeBitmap(e) {
        return this.reader.decodeWithState(e);
      }
    }
    class ai extends Lt {
      /**
       * Creates an instance of BrowserPDF417Reader.
       * @param {number} [timeBetweenScansMillis=500] the time delay between subsequent decode tries
       */
      constructor(e = 500) {
        super(new Xe(), e);
      }
    }
    class li extends Lt {
      /**
       * Creates an instance of BrowserQRCodeReader.
       * @param {number} [timeBetweenScansMillis=500] the time delay between subsequent decode tries
       */
      constructor(e = 500) {
        super(new Mt(), e);
      }
    }
    var jn;
    (function(u) {
      u[u.ERROR_CORRECTION = 0] = "ERROR_CORRECTION", u[u.CHARACTER_SET = 1] = "CHARACTER_SET", u[u.DATA_MATRIX_SHAPE = 2] = "DATA_MATRIX_SHAPE", u[u.MIN_SIZE = 3] = "MIN_SIZE", u[u.MAX_SIZE = 4] = "MAX_SIZE", u[u.MARGIN = 5] = "MARGIN", u[u.PDF417_COMPACT = 6] = "PDF417_COMPACT", u[u.PDF417_COMPACTION = 7] = "PDF417_COMPACTION", u[u.PDF417_DIMENSIONS = 8] = "PDF417_DIMENSIONS", u[u.AZTEC_LAYERS = 9] = "AZTEC_LAYERS", u[u.QR_VERSION = 10] = "QR_VERSION";
    })(jn || (jn = {}));
    var Pe = jn;
    class qn {
      /**
       * A reed solomon error-correcting encoding constructor is created by
       * passing as Galois Field with of size equal to the number of code
       * words (symbols) in the alphabet (the number of values in each
       * element of arrays that are encoded/decoded).
       * @param field A galois field with a number of elements equal to the size
       * of the alphabet of symbols to encode.
       */
      constructor(e) {
        this.field = e, this.cachedGenerators = [], this.cachedGenerators.push(new $e(e, Int32Array.from([1])));
      }
      buildGenerator(e) {
        const t = this.cachedGenerators;
        if (e >= t.length) {
          let n = t[t.length - 1];
          const r = this.field;
          for (let i = t.length; i <= e; i++) {
            const s = n.multiply(new $e(r, Int32Array.from([1, r.exp(i - 1 + r.getGeneratorBase())])));
            t.push(s), n = s;
          }
        }
        return t[e];
      }
      /**
       * <p>Encode a sequence of code words (symbols) using Reed-Solomon to allow decoders
       * to detect and correct errors that may have been introduced when the resulting
       * data is stored or transmitted.</p>
       *
       * @param toEncode array used for both and output. Caller initializes the array with
       * the code words (symbols) to be encoded followed by empty elements allocated to make
       * space for error-correction code words in the encoded output. The array contains
       * the encdoded output when encode returns. Code words are encoded as numbers from
       * 0 to n-1, where n is the number of possible code words (symbols), as determined
       * by the size of the Galois Field passed in the constructor of this object.
       * @param ecBytes the number of elements reserved in the array (first parameter)
       * to store error-correction code words. Thus, the number of code words (symbols)
       * to encode in the first parameter is thus toEncode.length - ecBytes.
       * Note, the use of "bytes" in the name of this parameter is misleading, as there may
       * be more or fewer than 256 symbols being encoded, as determined by the number of
       * elements in the Galois Field passed as a constructor to this object.
       * @throws IllegalArgumentException thrown in response to validation errros.
       */
      encode(e, t) {
        if (t === 0)
          throw new D("No error correction bytes");
        const n = e.length - t;
        if (n <= 0)
          throw new D("No data bytes provided");
        const r = this.buildGenerator(t), i = new Int32Array(n);
        re.arraycopy(e, 0, i, 0, n);
        let s = new $e(this.field, i);
        s = s.multiplyByMonomial(t, 1);
        const a = s.divide(r)[1].getCoefficients(), l = t - a.length;
        for (let c = 0; c < l; c++)
          e[n + c] = 0;
        re.arraycopy(a, 0, e, n + l, a.length);
      }
    }
    class Oe {
      constructor() {
      }
      /**
       * Apply mask penalty rule 1 and return the penalty. Find repetitive cells with the same color and
       * give penalty to them. Example: 00000 or 11111.
       */
      static applyMaskPenaltyRule1(e) {
        return Oe.applyMaskPenaltyRule1Internal(e, !0) + Oe.applyMaskPenaltyRule1Internal(e, !1);
      }
      /**
       * Apply mask penalty rule 2 and return the penalty. Find 2x2 blocks with the same color and give
       * penalty to them. This is actually equivalent to the spec's rule, which is to find MxN blocks and give a
       * penalty proportional to (M-1)x(N-1), because this is the number of 2x2 blocks inside such a block.
       */
      static applyMaskPenaltyRule2(e) {
        let t = 0;
        const n = e.getArray(), r = e.getWidth(), i = e.getHeight();
        for (let s = 0; s < i - 1; s++) {
          const o = n[s];
          for (let a = 0; a < r - 1; a++) {
            const l = o[a];
            l === o[a + 1] && l === n[s + 1][a] && l === n[s + 1][a + 1] && t++;
          }
        }
        return Oe.N2 * t;
      }
      /**
       * Apply mask penalty rule 3 and return the penalty. Find consecutive runs of 1:1:3:1:1:4
       * starting with black, or 4:1:1:3:1:1 starting with white, and give penalty to them.  If we
       * find patterns like 000010111010000, we give penalty once.
       */
      static applyMaskPenaltyRule3(e) {
        let t = 0;
        const n = e.getArray(), r = e.getWidth(), i = e.getHeight();
        for (let s = 0; s < i; s++)
          for (let o = 0; o < r; o++) {
            const a = n[s];
            o + 6 < r && a[o] === 1 && a[o + 1] === 0 && a[o + 2] === 1 && a[o + 3] === 1 && a[o + 4] === 1 && a[o + 5] === 0 && a[o + 6] === 1 && (Oe.isWhiteHorizontal(a, o - 4, o) || Oe.isWhiteHorizontal(a, o + 7, o + 11)) && t++, s + 6 < i && n[s][o] === 1 && n[s + 1][o] === 0 && n[s + 2][o] === 1 && n[s + 3][o] === 1 && n[s + 4][o] === 1 && n[s + 5][o] === 0 && n[s + 6][o] === 1 && (Oe.isWhiteVertical(n, o, s - 4, s) || Oe.isWhiteVertical(n, o, s + 7, s + 11)) && t++;
          }
        return t * Oe.N3;
      }
      static isWhiteHorizontal(e, t, n) {
        t = Math.max(t, 0), n = Math.min(n, e.length);
        for (let r = t; r < n; r++)
          if (e[r] === 1)
            return !1;
        return !0;
      }
      static isWhiteVertical(e, t, n, r) {
        n = Math.max(n, 0), r = Math.min(r, e.length);
        for (let i = n; i < r; i++)
          if (e[i][t] === 1)
            return !1;
        return !0;
      }
      /**
       * Apply mask penalty rule 4 and return the penalty. Calculate the ratio of dark cells and give
       * penalty if the ratio is far from 50%. It gives 10 penalty for 5% distance.
       */
      static applyMaskPenaltyRule4(e) {
        let t = 0;
        const n = e.getArray(), r = e.getWidth(), i = e.getHeight();
        for (let a = 0; a < i; a++) {
          const l = n[a];
          for (let c = 0; c < r; c++)
            l[c] === 1 && t++;
        }
        const s = e.getHeight() * e.getWidth();
        return Math.floor(Math.abs(t * 2 - s) * 10 / s) * Oe.N4;
      }
      /**
       * Return the mask bit for "getMaskPattern" at "x" and "y". See 8.8 of JISX0510:2004 for mask
       * pattern conditions.
       */
      static getDataMaskBit(e, t, n) {
        let r, i;
        switch (e) {
          case 0:
            r = n + t & 1;
            break;
          case 1:
            r = n & 1;
            break;
          case 2:
            r = t % 3;
            break;
          case 3:
            r = (n + t) % 3;
            break;
          case 4:
            r = Math.floor(n / 2) + Math.floor(t / 3) & 1;
            break;
          case 5:
            i = n * t, r = (i & 1) + i % 3;
            break;
          case 6:
            i = n * t, r = (i & 1) + i % 3 & 1;
            break;
          case 7:
            i = n * t, r = i % 3 + (n + t & 1) & 1;
            break;
          default:
            throw new D("Invalid mask pattern: " + e);
        }
        return r === 0;
      }
      /**
       * Helper function for applyMaskPenaltyRule1. We need this for doing this calculation in both
       * vertical and horizontal orders respectively.
       */
      static applyMaskPenaltyRule1Internal(e, t) {
        let n = 0;
        const r = t ? e.getHeight() : e.getWidth(), i = t ? e.getWidth() : e.getHeight(), s = e.getArray();
        for (let o = 0; o < r; o++) {
          let a = 0, l = -1;
          for (let c = 0; c < i; c++) {
            const f = t ? s[o][c] : s[c][o];
            f === l ? a++ : (a >= 5 && (n += Oe.N1 + (a - 5)), a = 1, l = f);
          }
          a >= 5 && (n += Oe.N1 + (a - 5));
        }
        return n;
      }
    }
    Oe.N1 = 3, Oe.N2 = 3, Oe.N3 = 40, Oe.N4 = 10;
    class Nn {
      constructor(e, t) {
        this.width = e, this.height = t;
        const n = new Array(t);
        for (let r = 0; r !== t; r++)
          n[r] = new Uint8Array(e);
        this.bytes = n;
      }
      getHeight() {
        return this.height;
      }
      getWidth() {
        return this.width;
      }
      get(e, t) {
        return this.bytes[t][e];
      }
      /**
       * @return an internal representation as bytes, in row-major order. array[y][x] represents point (x,y)
       */
      getArray() {
        return this.bytes;
      }
      // TYPESCRIPTPORT: preffer to let two methods instead of override to avoid type comparison inside
      setNumber(e, t, n) {
        this.bytes[t][e] = n;
      }
      // public set(x: number /*int*/, y: number /*int*/, value: number /*int*/): void {
      //   bytes[y][x] = (byte) value
      // }
      setBoolean(e, t, n) {
        this.bytes[t][e] = /*(byte) */
        n ? 1 : 0;
      }
      clear(e) {
        for (const t of this.bytes)
          me.fill(t, e);
      }
      equals(e) {
        if (!(e instanceof Nn))
          return !1;
        const t = e;
        if (this.width !== t.width || this.height !== t.height)
          return !1;
        for (let n = 0, r = this.height; n < r; ++n) {
          const i = this.bytes[n], s = t.bytes[n];
          for (let o = 0, a = this.width; o < a; ++o)
            if (i[o] !== s[o])
              return !1;
        }
        return !0;
      }
      /*@Override*/
      toString() {
        const e = new ge();
        for (let t = 0, n = this.height; t < n; ++t) {
          const r = this.bytes[t];
          for (let i = 0, s = this.width; i < s; ++i)
            switch (r[i]) {
              case 0:
                e.append(" 0");
                break;
              case 1:
                e.append(" 1");
                break;
              default:
                e.append("  ");
                break;
            }
          e.append(`
`);
        }
        return e.toString();
      }
    }
    class Vt {
      constructor() {
        this.maskPattern = -1;
      }
      getMode() {
        return this.mode;
      }
      getECLevel() {
        return this.ecLevel;
      }
      getVersion() {
        return this.version;
      }
      getMaskPattern() {
        return this.maskPattern;
      }
      getMatrix() {
        return this.matrix;
      }
      /*@Override*/
      toString() {
        const e = new ge();
        return e.append(`<<
`), e.append(" mode: "), e.append(this.mode ? this.mode.toString() : "null"), e.append(`
 ecLevel: `), e.append(this.ecLevel ? this.ecLevel.toString() : "null"), e.append(`
 version: `), e.append(this.version ? this.version.toString() : "null"), e.append(`
 maskPattern: `), e.append(this.maskPattern.toString()), this.matrix ? (e.append(`
 matrix:
`), e.append(this.matrix.toString())) : e.append(`
 matrix: null
`), e.append(`>>
`), e.toString();
      }
      setMode(e) {
        this.mode = e;
      }
      setECLevel(e) {
        this.ecLevel = e;
      }
      setVersion(e) {
        this.version = e;
      }
      setMaskPattern(e) {
        this.maskPattern = e;
      }
      setMatrix(e) {
        this.matrix = e;
      }
      // Check if "mask_pattern" is valid.
      static isValidMaskPattern(e) {
        return e >= 0 && e < Vt.NUM_MASK_PATTERNS;
      }
    }
    Vt.NUM_MASK_PATTERNS = 8;
    class Ee extends E {
    }
    Ee.kind = "WriterException";
    class Z {
      constructor() {
      }
      // Set all cells to -1 (TYPESCRIPTPORT: 255).  -1 (TYPESCRIPTPORT: 255) means that the cell is empty (not set yet).
      //
      // JAVAPORT: We shouldn't need to do this at all. The code should be rewritten to begin encoding
      // with the ByteMatrix initialized all to zero.
      static clearMatrix(e) {
        e.clear(
          /*(byte) */
          /*-1*/
          255
        );
      }
      // Build 2D matrix of QR Code from "dataBits" with "ecLevel", "version" and "getMaskPattern". On
      // success, store the result in "matrix" and return true.
      static buildMatrix(e, t, n, r, i) {
        Z.clearMatrix(i), Z.embedBasicPatterns(n, i), Z.embedTypeInfo(t, r, i), Z.maybeEmbedVersionInfo(n, i), Z.embedDataBits(e, r, i);
      }
      // Embed basic patterns. On success, modify the matrix and return true.
      // The basic patterns are:
      // - Position detection patterns
      // - Timing patterns
      // - Dark dot at the left bottom corner
      // - Position adjustment patterns, if need be
      static embedBasicPatterns(e, t) {
        Z.embedPositionDetectionPatternsAndSeparators(t), Z.embedDarkDotAtLeftBottomCorner(t), Z.maybeEmbedPositionAdjustmentPatterns(e, t), Z.embedTimingPatterns(t);
      }
      // Embed type information. On success, modify the matrix.
      static embedTypeInfo(e, t, n) {
        const r = new le();
        Z.makeTypeInfoBits(e, t, r);
        for (let i = 0, s = r.getSize(); i < s; ++i) {
          const o = r.get(r.getSize() - 1 - i), a = Z.TYPE_INFO_COORDINATES[i], l = a[0], c = a[1];
          if (n.setBoolean(l, c, o), i < 8) {
            const f = n.getWidth() - i - 1;
            n.setBoolean(f, 8, o);
          } else {
            const d = n.getHeight() - 7 + (i - 8);
            n.setBoolean(8, d, o);
          }
        }
      }
      // Embed version information if need be. On success, modify the matrix and return true.
      // See 8.10 of JISX0510:2004 (p.47) for how to embed version information.
      static maybeEmbedVersionInfo(e, t) {
        if (e.getVersionNumber() < 7)
          return;
        const n = new le();
        Z.makeVersionInfoBits(e, n);
        let r = 6 * 3 - 1;
        for (let i = 0; i < 6; ++i)
          for (let s = 0; s < 3; ++s) {
            const o = n.get(r);
            r--, t.setBoolean(i, t.getHeight() - 11 + s, o), t.setBoolean(t.getHeight() - 11 + s, i, o);
          }
      }
      // Embed "dataBits" using "getMaskPattern". On success, modify the matrix and return true.
      // For debugging purposes, it skips masking process if "getMaskPattern" is -1(TYPESCRIPTPORT: 255).
      // See 8.7 of JISX0510:2004 (p.38) for how to embed data bits.
      static embedDataBits(e, t, n) {
        let r = 0, i = -1, s = n.getWidth() - 1, o = n.getHeight() - 1;
        for (; s > 0; ) {
          for (s === 6 && (s -= 1); o >= 0 && o < n.getHeight(); ) {
            for (let a = 0; a < 2; ++a) {
              const l = s - a;
              if (!Z.isEmpty(n.get(l, o)))
                continue;
              let c;
              r < e.getSize() ? (c = e.get(r), ++r) : c = !1, t !== 255 && Oe.getDataMaskBit(t, l, o) && (c = !c), n.setBoolean(l, o, c);
            }
            o += i;
          }
          i = -i, o += i, s -= 2;
        }
        if (r !== e.getSize())
          throw new Ee("Not all bits consumed: " + r + "/" + e.getSize());
      }
      // Return the position of the most significant bit set (one: to) in the "value". The most
      // significant bit is position 32. If there is no bit set, return 0. Examples:
      // - findMSBSet(0) => 0
      // - findMSBSet(1) => 1
      // - findMSBSet(255) => 8
      static findMSBSet(e) {
        return 32 - j.numberOfLeadingZeros(e);
      }
      // Calculate BCH (Bose-Chaudhuri-Hocquenghem) code for "value" using polynomial "poly". The BCH
      // code is used for encoding type information and version information.
      // Example: Calculation of version information of 7.
      // f(x) is created from 7.
      //   - 7 = 000111 in 6 bits
      //   - f(x) = x^2 + x^1 + x^0
      // g(x) is given by the standard (p. 67)
      //   - g(x) = x^12 + x^11 + x^10 + x^9 + x^8 + x^5 + x^2 + 1
      // Multiply f(x) by x^(18 - 6)
      //   - f'(x) = f(x) * x^(18 - 6)
      //   - f'(x) = x^14 + x^13 + x^12
      // Calculate the remainder of f'(x) / g(x)
      //         x^2
      //         __________________________________________________
      //   g(x) )x^14 + x^13 + x^12
      //         x^14 + x^13 + x^12 + x^11 + x^10 + x^7 + x^4 + x^2
      //         --------------------------------------------------
      //                              x^11 + x^10 + x^7 + x^4 + x^2
      //
      // The remainder is x^11 + x^10 + x^7 + x^4 + x^2
      // Encode it in binary: 110010010100
      // The return value is 0xc94 (1100 1001 0100)
      //
      // Since all coefficients in the polynomials are 1 or 0, we can do the calculation by bit
      // operations. We don't care if coefficients are positive or negative.
      static calculateBCHCode(e, t) {
        if (t === 0)
          throw new D("0 polynomial");
        const n = Z.findMSBSet(t);
        for (e <<= n - 1; Z.findMSBSet(e) >= n; )
          e ^= t << Z.findMSBSet(e) - n;
        return e;
      }
      // Make bit vector of type information. On success, store the result in "bits" and return true.
      // Encode error correction level and mask pattern. See 8.9 of
      // JISX0510:2004 (p.45) for details.
      static makeTypeInfoBits(e, t, n) {
        if (!Vt.isValidMaskPattern(t))
          throw new Ee("Invalid mask pattern");
        const r = e.getBits() << 3 | t;
        n.appendBits(r, 5);
        const i = Z.calculateBCHCode(r, Z.TYPE_INFO_POLY);
        n.appendBits(i, 10);
        const s = new le();
        if (s.appendBits(Z.TYPE_INFO_MASK_PATTERN, 15), n.xor(s), n.getSize() !== 15)
          throw new Ee("should not happen but we got: " + n.getSize());
      }
      // Make bit vector of version information. On success, store the result in "bits" and return true.
      // See 8.10 of JISX0510:2004 (p.45) for details.
      static makeVersionInfoBits(e, t) {
        t.appendBits(e.getVersionNumber(), 6);
        const n = Z.calculateBCHCode(e.getVersionNumber(), Z.VERSION_INFO_POLY);
        if (t.appendBits(n, 12), t.getSize() !== 18)
          throw new Ee("should not happen but we got: " + t.getSize());
      }
      // Check if "value" is empty.
      static isEmpty(e) {
        return e === 255;
      }
      static embedTimingPatterns(e) {
        for (let t = 8; t < e.getWidth() - 8; ++t) {
          const n = (t + 1) % 2;
          Z.isEmpty(e.get(t, 6)) && e.setNumber(t, 6, n), Z.isEmpty(e.get(6, t)) && e.setNumber(6, t, n);
        }
      }
      // Embed the lonely dark dot at left bottom corner. JISX0510:2004 (p.46)
      static embedDarkDotAtLeftBottomCorner(e) {
        if (e.get(8, e.getHeight() - 8) === 0)
          throw new Ee();
        e.setNumber(8, e.getHeight() - 8, 1);
      }
      static embedHorizontalSeparationPattern(e, t, n) {
        for (let r = 0; r < 8; ++r) {
          if (!Z.isEmpty(n.get(e + r, t)))
            throw new Ee();
          n.setNumber(e + r, t, 0);
        }
      }
      static embedVerticalSeparationPattern(e, t, n) {
        for (let r = 0; r < 7; ++r) {
          if (!Z.isEmpty(n.get(e, t + r)))
            throw new Ee();
          n.setNumber(e, t + r, 0);
        }
      }
      static embedPositionAdjustmentPattern(e, t, n) {
        for (let r = 0; r < 5; ++r) {
          const i = Z.POSITION_ADJUSTMENT_PATTERN[r];
          for (let s = 0; s < 5; ++s)
            n.setNumber(e + s, t + r, i[s]);
        }
      }
      static embedPositionDetectionPattern(e, t, n) {
        for (let r = 0; r < 7; ++r) {
          const i = Z.POSITION_DETECTION_PATTERN[r];
          for (let s = 0; s < 7; ++s)
            n.setNumber(e + s, t + r, i[s]);
        }
      }
      // Embed position detection patterns and surrounding vertical/horizontal separators.
      static embedPositionDetectionPatternsAndSeparators(e) {
        const t = Z.POSITION_DETECTION_PATTERN[0].length;
        Z.embedPositionDetectionPattern(0, 0, e), Z.embedPositionDetectionPattern(e.getWidth() - t, 0, e), Z.embedPositionDetectionPattern(0, e.getWidth() - t, e);
        const n = 8;
        Z.embedHorizontalSeparationPattern(0, n - 1, e), Z.embedHorizontalSeparationPattern(e.getWidth() - n, n - 1, e), Z.embedHorizontalSeparationPattern(0, e.getWidth() - n, e);
        const r = 7;
        Z.embedVerticalSeparationPattern(r, 0, e), Z.embedVerticalSeparationPattern(e.getHeight() - r - 1, 0, e), Z.embedVerticalSeparationPattern(r, e.getHeight() - r, e);
      }
      // Embed position adjustment patterns if need be.
      static maybeEmbedPositionAdjustmentPatterns(e, t) {
        if (e.getVersionNumber() < 2)
          return;
        const n = e.getVersionNumber() - 1, r = Z.POSITION_ADJUSTMENT_PATTERN_COORDINATE_TABLE[n];
        for (let i = 0, s = r.length; i !== s; i++) {
          const o = r[i];
          if (o >= 0)
            for (let a = 0; a !== s; a++) {
              const l = r[a];
              l >= 0 && Z.isEmpty(t.get(l, o)) && Z.embedPositionAdjustmentPattern(l - 2, o - 2, t);
            }
        }
      }
    }
    Z.POSITION_DETECTION_PATTERN = Array.from([
      Int32Array.from([1, 1, 1, 1, 1, 1, 1]),
      Int32Array.from([1, 0, 0, 0, 0, 0, 1]),
      Int32Array.from([1, 0, 1, 1, 1, 0, 1]),
      Int32Array.from([1, 0, 1, 1, 1, 0, 1]),
      Int32Array.from([1, 0, 1, 1, 1, 0, 1]),
      Int32Array.from([1, 0, 0, 0, 0, 0, 1]),
      Int32Array.from([1, 1, 1, 1, 1, 1, 1])
    ]), Z.POSITION_ADJUSTMENT_PATTERN = Array.from([
      Int32Array.from([1, 1, 1, 1, 1]),
      Int32Array.from([1, 0, 0, 0, 1]),
      Int32Array.from([1, 0, 1, 0, 1]),
      Int32Array.from([1, 0, 0, 0, 1]),
      Int32Array.from([1, 1, 1, 1, 1])
    ]), Z.POSITION_ADJUSTMENT_PATTERN_COORDINATE_TABLE = Array.from([
      Int32Array.from([-1, -1, -1, -1, -1, -1, -1]),
      Int32Array.from([6, 18, -1, -1, -1, -1, -1]),
      Int32Array.from([6, 22, -1, -1, -1, -1, -1]),
      Int32Array.from([6, 26, -1, -1, -1, -1, -1]),
      Int32Array.from([6, 30, -1, -1, -1, -1, -1]),
      Int32Array.from([6, 34, -1, -1, -1, -1, -1]),
      Int32Array.from([6, 22, 38, -1, -1, -1, -1]),
      Int32Array.from([6, 24, 42, -1, -1, -1, -1]),
      Int32Array.from([6, 26, 46, -1, -1, -1, -1]),
      Int32Array.from([6, 28, 50, -1, -1, -1, -1]),
      Int32Array.from([6, 30, 54, -1, -1, -1, -1]),
      Int32Array.from([6, 32, 58, -1, -1, -1, -1]),
      Int32Array.from([6, 34, 62, -1, -1, -1, -1]),
      Int32Array.from([6, 26, 46, 66, -1, -1, -1]),
      Int32Array.from([6, 26, 48, 70, -1, -1, -1]),
      Int32Array.from([6, 26, 50, 74, -1, -1, -1]),
      Int32Array.from([6, 30, 54, 78, -1, -1, -1]),
      Int32Array.from([6, 30, 56, 82, -1, -1, -1]),
      Int32Array.from([6, 30, 58, 86, -1, -1, -1]),
      Int32Array.from([6, 34, 62, 90, -1, -1, -1]),
      Int32Array.from([6, 28, 50, 72, 94, -1, -1]),
      Int32Array.from([6, 26, 50, 74, 98, -1, -1]),
      Int32Array.from([6, 30, 54, 78, 102, -1, -1]),
      Int32Array.from([6, 28, 54, 80, 106, -1, -1]),
      Int32Array.from([6, 32, 58, 84, 110, -1, -1]),
      Int32Array.from([6, 30, 58, 86, 114, -1, -1]),
      Int32Array.from([6, 34, 62, 90, 118, -1, -1]),
      Int32Array.from([6, 26, 50, 74, 98, 122, -1]),
      Int32Array.from([6, 30, 54, 78, 102, 126, -1]),
      Int32Array.from([6, 26, 52, 78, 104, 130, -1]),
      Int32Array.from([6, 30, 56, 82, 108, 134, -1]),
      Int32Array.from([6, 34, 60, 86, 112, 138, -1]),
      Int32Array.from([6, 30, 58, 86, 114, 142, -1]),
      Int32Array.from([6, 34, 62, 90, 118, 146, -1]),
      Int32Array.from([6, 30, 54, 78, 102, 126, 150]),
      Int32Array.from([6, 24, 50, 76, 102, 128, 154]),
      Int32Array.from([6, 28, 54, 80, 106, 132, 158]),
      Int32Array.from([6, 32, 58, 84, 110, 136, 162]),
      Int32Array.from([6, 26, 54, 82, 110, 138, 166]),
      Int32Array.from([6, 30, 58, 86, 114, 142, 170])
    ]), Z.TYPE_INFO_COORDINATES = Array.from([
      Int32Array.from([8, 0]),
      Int32Array.from([8, 1]),
      Int32Array.from([8, 2]),
      Int32Array.from([8, 3]),
      Int32Array.from([8, 4]),
      Int32Array.from([8, 5]),
      Int32Array.from([8, 7]),
      Int32Array.from([8, 8]),
      Int32Array.from([7, 8]),
      Int32Array.from([5, 8]),
      Int32Array.from([4, 8]),
      Int32Array.from([3, 8]),
      Int32Array.from([2, 8]),
      Int32Array.from([1, 8]),
      Int32Array.from([0, 8])
    ]), Z.VERSION_INFO_POLY = 7973, Z.TYPE_INFO_POLY = 1335, Z.TYPE_INFO_MASK_PATTERN = 21522;
    class ci {
      constructor(e, t) {
        this.dataBytes = e, this.errorCorrectionBytes = t;
      }
      getDataBytes() {
        return this.dataBytes;
      }
      getErrorCorrectionBytes() {
        return this.errorCorrectionBytes;
      }
    }
    class be {
      // TYPESCRIPTPORT: changed to UTF8, the default for js
      constructor() {
      }
      // The mask penalty calculation is complicated.  See Table 21 of JISX0510:2004 (p.45) for details.
      // Basically it applies four rules and summate all penalties.
      static calculateMaskPenalty(e) {
        return Oe.applyMaskPenaltyRule1(e) + Oe.applyMaskPenaltyRule2(e) + Oe.applyMaskPenaltyRule3(e) + Oe.applyMaskPenaltyRule4(e);
      }
      /**
       * @param content text to encode
       * @param ecLevel error correction level to use
       * @return {@link QRCode} representing the encoded QR code
       * @throws WriterException if encoding can't succeed, because of for example invalid content
       *   or configuration
       */
      // public static encode(content: string, ecLevel: ErrorCorrectionLevel): QRCode /*throws WriterException*/ {
      //   return encode(content, ecLevel, null)
      // }
      static encode(e, t, n = null) {
        let r = be.DEFAULT_BYTE_MODE_ENCODING;
        const i = n !== null && n.get(Pe.CHARACTER_SET) !== void 0;
        i && (r = n.get(Pe.CHARACTER_SET).toString());
        const s = this.chooseMode(e, r), o = new le();
        if (s === Y.BYTE && (i || be.DEFAULT_BYTE_MODE_ENCODING !== r)) {
          const F = k.getCharacterSetECIByName(r);
          F !== void 0 && this.appendECI(F, o);
        }
        this.appendModeInfo(s, o);
        const a = new le();
        this.appendBytes(e, s, a, r);
        let l;
        if (n !== null && n.get(Pe.QR_VERSION) !== void 0) {
          const F = Number.parseInt(n.get(Pe.QR_VERSION).toString(), 10);
          l = X.getVersionForNumber(F);
          const L = this.calculateBitsNeeded(s, o, a, l);
          if (!this.willFit(L, l, t))
            throw new Ee("Data too big for requested version");
        } else
          l = this.recommendVersion(t, s, o, a);
        const c = new le();
        c.appendBitArray(o);
        const f = s === Y.BYTE ? a.getSizeInBytes() : e.length;
        this.appendLengthInfo(f, l, s, c), c.appendBitArray(a);
        const d = l.getECBlocksForLevel(t), C = l.getTotalCodewords() - d.getTotalECCodewords();
        this.terminateBits(C, c);
        const p = this.interleaveWithECBytes(c, l.getTotalCodewords(), C, d.getNumBlocks()), b = new Vt();
        b.setECLevel(t), b.setMode(s), b.setVersion(l);
        const y = l.getDimensionForVersion(), _ = new Nn(y, y), P = this.chooseMaskPattern(p, t, l, _);
        return b.setMaskPattern(P), Z.buildMatrix(p, t, l, P, _), b.setMatrix(_), b;
      }
      /**
       * Decides the smallest version of QR code that will contain all of the provided data.
       *
       * @throws WriterException if the data cannot fit in any version
       */
      static recommendVersion(e, t, n, r) {
        const i = this.calculateBitsNeeded(t, n, r, X.getVersionForNumber(1)), s = this.chooseVersion(i, e), o = this.calculateBitsNeeded(t, n, r, s);
        return this.chooseVersion(o, e);
      }
      static calculateBitsNeeded(e, t, n, r) {
        return t.getSize() + e.getCharacterCountBits(r) + n.getSize();
      }
      /**
       * @return the code point of the table used in alphanumeric mode or
       *  -1 if there is no corresponding code in the table.
       */
      static getAlphanumericCode(e) {
        return e < be.ALPHANUMERIC_TABLE.length ? be.ALPHANUMERIC_TABLE[e] : -1;
      }
      // public static chooseMode(content: string): Mode {
      //   return chooseMode(content, null);
      // }
      /**
       * Choose the best mode by examining the content. Note that 'encoding' is used as a hint;
       * if it is Shift_JIS, and the input is only double-byte Kanji, then we return {@link Mode#KANJI}.
       */
      static chooseMode(e, t = null) {
        if (k.SJIS.getName() === t && this.isOnlyDoubleByteKanji(e))
          return Y.KANJI;
        let n = !1, r = !1;
        for (let i = 0, s = e.length; i < s; ++i) {
          const o = e.charAt(i);
          if (be.isDigit(o))
            n = !0;
          else if (this.getAlphanumericCode(o.charCodeAt(0)) !== -1)
            r = !0;
          else
            return Y.BYTE;
        }
        return r ? Y.ALPHANUMERIC : n ? Y.NUMERIC : Y.BYTE;
      }
      static isOnlyDoubleByteKanji(e) {
        let t;
        try {
          t = Je.encode(e, k.SJIS);
        } catch {
          return !1;
        }
        const n = t.length;
        if (n % 2 !== 0)
          return !1;
        for (let r = 0; r < n; r += 2) {
          const i = t[r] & 255;
          if ((i < 129 || i > 159) && (i < 224 || i > 235))
            return !1;
        }
        return !0;
      }
      static chooseMaskPattern(e, t, n, r) {
        let i = Number.MAX_SAFE_INTEGER, s = -1;
        for (let o = 0; o < Vt.NUM_MASK_PATTERNS; o++) {
          Z.buildMatrix(e, t, n, o, r);
          let a = this.calculateMaskPenalty(r);
          a < i && (i = a, s = o);
        }
        return s;
      }
      static chooseVersion(e, t) {
        for (let n = 1; n <= 40; n++) {
          const r = X.getVersionForNumber(n);
          if (be.willFit(e, r, t))
            return r;
        }
        throw new Ee("Data too big");
      }
      /**
       * @return true if the number of input bits will fit in a code with the specified version and
       * error correction level.
       */
      static willFit(e, t, n) {
        const r = t.getTotalCodewords(), s = t.getECBlocksForLevel(n).getTotalECCodewords(), o = r - s, a = (e + 7) / 8;
        return o >= a;
      }
      /**
       * Terminate bits as described in 8.4.8 and 8.4.9 of JISX0510:2004 (p.24).
       */
      static terminateBits(e, t) {
        const n = e * 8;
        if (t.getSize() > n)
          throw new Ee("data bits cannot fit in the QR Code" + t.getSize() + " > " + n);
        for (let s = 0; s < 4 && t.getSize() < n; ++s)
          t.appendBit(!1);
        const r = t.getSize() & 7;
        if (r > 0)
          for (let s = r; s < 8; s++)
            t.appendBit(!1);
        const i = e - t.getSizeInBytes();
        for (let s = 0; s < i; ++s)
          t.appendBits(s & 1 ? 17 : 236, 8);
        if (t.getSize() !== n)
          throw new Ee("Bits size does not equal capacity");
      }
      /**
       * Get number of data bytes and number of error correction bytes for block id "blockID". Store
       * the result in "numDataBytesInBlock", and "numECBytesInBlock". See table 12 in 8.5.1 of
       * JISX0510:2004 (p.30)
       */
      static getNumDataBytesAndNumECBytesForBlockID(e, t, n, r, i, s) {
        if (r >= n)
          throw new Ee("Block ID too large");
        const o = e % n, a = n - o, l = Math.floor(e / n), c = l + 1, f = Math.floor(t / n), d = f + 1, C = l - f, p = c - d;
        if (C !== p)
          throw new Ee("EC bytes mismatch");
        if (n !== a + o)
          throw new Ee("RS blocks mismatch");
        if (e !== (f + C) * a + (d + p) * o)
          throw new Ee("Total bytes mismatch");
        r < a ? (i[0] = f, s[0] = C) : (i[0] = d, s[0] = p);
      }
      /**
       * Interleave "bits" with corresponding error correction bytes. On success, store the result in
       * "result". The interleave rule is complicated. See 8.6 of JISX0510:2004 (p.37) for details.
       */
      static interleaveWithECBytes(e, t, n, r) {
        if (e.getSizeInBytes() !== n)
          throw new Ee("Number of bits and data bytes does not match");
        let i = 0, s = 0, o = 0;
        const a = new Array();
        for (let c = 0; c < r; ++c) {
          const f = new Int32Array(1), d = new Int32Array(1);
          be.getNumDataBytesAndNumECBytesForBlockID(t, n, r, c, f, d);
          const C = f[0], p = new Uint8Array(C);
          e.toBytes(8 * i, p, 0, C);
          const b = be.generateECBytes(p, d[0]);
          a.push(new ci(p, b)), s = Math.max(s, C), o = Math.max(o, b.length), i += f[0];
        }
        if (n !== i)
          throw new Ee("Data bytes does not match offset");
        const l = new le();
        for (let c = 0; c < s; ++c)
          for (const f of a) {
            const d = f.getDataBytes();
            c < d.length && l.appendBits(d[c], 8);
          }
        for (let c = 0; c < o; ++c)
          for (const f of a) {
            const d = f.getErrorCorrectionBytes();
            c < d.length && l.appendBits(d[c], 8);
          }
        if (t !== l.getSizeInBytes())
          throw new Ee("Interleaving error: " + t + " and " + l.getSizeInBytes() + " differ.");
        return l;
      }
      static generateECBytes(e, t) {
        const n = e.length, r = new Int32Array(n + t);
        for (let s = 0; s < n; s++)
          r[s] = e[s] & 255;
        new qn(ce.QR_CODE_FIELD_256).encode(r, t);
        const i = new Uint8Array(t);
        for (let s = 0; s < t; s++)
          i[s] = /*(byte) */
          r[n + s];
        return i;
      }
      /**
       * Append mode info. On success, store the result in "bits".
       */
      static appendModeInfo(e, t) {
        t.appendBits(e.getBits(), 4);
      }
      /**
       * Append length info. On success, store the result in "bits".
       */
      static appendLengthInfo(e, t, n, r) {
        const i = n.getCharacterCountBits(t);
        if (e >= 1 << i)
          throw new Ee(e + " is bigger than " + ((1 << i) - 1));
        r.appendBits(e, i);
      }
      /**
       * Append "bytes" in "mode" mode (encoding) into "bits". On success, store the result in "bits".
       */
      static appendBytes(e, t, n, r) {
        switch (t) {
          case Y.NUMERIC:
            be.appendNumericBytes(e, n);
            break;
          case Y.ALPHANUMERIC:
            be.appendAlphanumericBytes(e, n);
            break;
          case Y.BYTE:
            be.append8BitBytes(e, n, r);
            break;
          case Y.KANJI:
            be.appendKanjiBytes(e, n);
            break;
          default:
            throw new Ee("Invalid mode: " + t);
        }
      }
      static getDigit(e) {
        return e.charCodeAt(0) - 48;
      }
      static isDigit(e) {
        const t = be.getDigit(e);
        return t >= 0 && t <= 9;
      }
      static appendNumericBytes(e, t) {
        const n = e.length;
        let r = 0;
        for (; r < n; ) {
          const i = be.getDigit(e.charAt(r));
          if (r + 2 < n) {
            const s = be.getDigit(e.charAt(r + 1)), o = be.getDigit(e.charAt(r + 2));
            t.appendBits(i * 100 + s * 10 + o, 10), r += 3;
          } else if (r + 1 < n) {
            const s = be.getDigit(e.charAt(r + 1));
            t.appendBits(i * 10 + s, 7), r += 2;
          } else
            t.appendBits(i, 4), r++;
        }
      }
      static appendAlphanumericBytes(e, t) {
        const n = e.length;
        let r = 0;
        for (; r < n; ) {
          const i = be.getAlphanumericCode(e.charCodeAt(r));
          if (i === -1)
            throw new Ee();
          if (r + 1 < n) {
            const s = be.getAlphanumericCode(e.charCodeAt(r + 1));
            if (s === -1)
              throw new Ee();
            t.appendBits(i * 45 + s, 11), r += 2;
          } else
            t.appendBits(i, 6), r++;
        }
      }
      static append8BitBytes(e, t, n) {
        let r;
        try {
          r = Je.encode(e, n);
        } catch (i) {
          throw new Ee(i);
        }
        for (let i = 0, s = r.length; i !== s; i++) {
          const o = r[i];
          t.appendBits(o, 8);
        }
      }
      /**
       * @throws WriterException
       */
      static appendKanjiBytes(e, t) {
        let n;
        try {
          n = Je.encode(e, k.SJIS);
        } catch (i) {
          throw new Ee(i);
        }
        const r = n.length;
        for (let i = 0; i < r; i += 2) {
          const s = n[i] & 255, o = n[i + 1] & 255, a = s << 8 & 4294967295 | o;
          let l = -1;
          if (a >= 33088 && a <= 40956 ? l = a - 33088 : a >= 57408 && a <= 60351 && (l = a - 49472), l === -1)
            throw new Ee("Invalid byte sequence");
          const c = (l >> 8) * 192 + (l & 255);
          t.appendBits(c, 13);
        }
      }
      static appendECI(e, t) {
        t.appendBits(Y.ECI.getBits(), 4), t.appendBits(e.getValue(), 8);
      }
    }
    be.ALPHANUMERIC_TABLE = Int32Array.from([
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      36,
      -1,
      -1,
      -1,
      37,
      38,
      -1,
      -1,
      -1,
      -1,
      39,
      40,
      -1,
      41,
      42,
      43,
      0,
      1,
      2,
      3,
      4,
      5,
      6,
      7,
      8,
      9,
      44,
      -1,
      -1,
      -1,
      -1,
      -1,
      -1,
      10,
      11,
      12,
      13,
      14,
      15,
      16,
      17,
      18,
      19,
      20,
      21,
      22,
      23,
      24,
      25,
      26,
      27,
      28,
      29,
      30,
      31,
      32,
      33,
      34,
      35,
      -1,
      -1,
      -1,
      -1,
      -1
    ]), be.DEFAULT_BYTE_MODE_ENCODING = k.UTF8.getName();
    class Gt {
      /**
       * Writes and renders a QRCode SVG element.
       *
       * @param contents
       * @param width
       * @param height
       * @param hints
       */
      write(e, t, n, r = null) {
        if (e.length === 0)
          throw new D("Found empty contents");
        if (t < 0 || n < 0)
          throw new D("Requested dimensions are too small: " + t + "x" + n);
        let i = pe.L, s = Gt.QUIET_ZONE_SIZE;
        r !== null && (r.get(Pe.ERROR_CORRECTION) !== void 0 && (i = pe.fromString(r.get(Pe.ERROR_CORRECTION).toString())), r.get(Pe.MARGIN) !== void 0 && (s = Number.parseInt(r.get(Pe.MARGIN).toString(), 10)));
        const o = be.encode(e, i, r);
        return this.renderResult(o, t, n, s);
      }
      /**
       * Renders the result and then appends it to the DOM.
       */
      writeToDom(e, t, n, r, i = null) {
        typeof e == "string" && (e = document.querySelector(e));
        const s = this.write(t, n, r, i);
        e && e.appendChild(s);
      }
      /**
       * Note that the input matrix uses 0 == white, 1 == black.
       * The output matrix uses 0 == black, 255 == white (i.e. an 8 bit greyscale bitmap).
       */
      renderResult(e, t, n, r) {
        const i = e.getMatrix();
        if (i === null)
          throw new gt();
        const s = i.getWidth(), o = i.getHeight(), a = s + r * 2, l = o + r * 2, c = Math.max(t, a), f = Math.max(n, l), d = Math.min(Math.floor(c / a), Math.floor(f / l)), C = Math.floor((c - s * d) / 2), p = Math.floor((f - o * d) / 2), b = this.createSVGElement(c, f);
        for (let y = 0, _ = p; y < o; y++, _ += d)
          for (let P = 0, F = C; P < s; P++, F += d)
            if (i.get(P, y) === 1) {
              const L = this.createSvgRectElement(F, _, d, d);
              b.appendChild(L);
            }
        return b;
      }
      /**
       * Creates a SVG element.
       *
       * @param w SVG's width attribute
       * @param h SVG's height attribute
       */
      createSVGElement(e, t) {
        const n = document.createElementNS(Gt.SVG_NS, "svg");
        return n.setAttributeNS(null, "height", e.toString()), n.setAttributeNS(null, "width", t.toString()), n;
      }
      /**
       * Creates a SVG rect element.
       *
       * @param x Element's x coordinate
       * @param y Element's y coordinate
       * @param w Element's width attribute
       * @param h Element's height attribute
       */
      createSvgRectElement(e, t, n, r) {
        const i = document.createElementNS(Gt.SVG_NS, "rect");
        return i.setAttributeNS(null, "x", e.toString()), i.setAttributeNS(null, "y", t.toString()), i.setAttributeNS(null, "height", n.toString()), i.setAttributeNS(null, "width", r.toString()), i.setAttributeNS(null, "fill", "#000000"), i;
      }
    }
    Gt.QUIET_ZONE_SIZE = 4, Gt.SVG_NS = "http://www.w3.org/2000/svg";
    class qt {
      /*@Override*/
      // public encode(contents: string, format: BarcodeFormat, width: number /*int*/, height: number /*int*/): BitMatrix
      //     /*throws WriterException */ {
      //   return encode(contents, format, width, height, null)
      // }
      /*@Override*/
      encode(e, t, n, r, i) {
        if (e.length === 0)
          throw new D("Found empty contents");
        if (t !== z.QR_CODE)
          throw new D("Can only encode QR_CODE, but got " + t);
        if (n < 0 || r < 0)
          throw new D(`Requested dimensions are too small: ${n}x${r}`);
        let s = pe.L, o = qt.QUIET_ZONE_SIZE;
        i !== null && (i.get(Pe.ERROR_CORRECTION) !== void 0 && (s = pe.fromString(i.get(Pe.ERROR_CORRECTION).toString())), i.get(Pe.MARGIN) !== void 0 && (o = Number.parseInt(i.get(Pe.MARGIN).toString(), 10)));
        const a = be.encode(e, s, i);
        return qt.renderResult(a, n, r, o);
      }
      // Note that the input matrix uses 0 == white, 1 == black, while the output matrix uses
      // 0 == black, 255 == white (i.e. an 8 bit greyscale bitmap).
      static renderResult(e, t, n, r) {
        const i = e.getMatrix();
        if (i === null)
          throw new gt();
        const s = i.getWidth(), o = i.getHeight(), a = s + r * 2, l = o + r * 2, c = Math.max(t, a), f = Math.max(n, l), d = Math.min(Math.floor(c / a), Math.floor(f / l)), C = Math.floor((c - s * d) / 2), p = Math.floor((f - o * d) / 2), b = new Fe(c, f);
        for (let y = 0, _ = p; y < o; y++, _ += d)
          for (let P = 0, F = C; P < s; P++, F += d)
            i.get(P, y) === 1 && b.setRegion(F, _, d, d);
        return b;
      }
    }
    qt.QUIET_ZONE_SIZE = 4;
    class hi {
      /*@Override*/
      // public encode(contents: string,
      //                         format: BarcodeFormat,
      //                         width: number /*int*/,
      //                         height: number /*int*/): BitMatrix /*throws WriterException */ {
      //   return encode(contents, format, width, height, null)
      // }
      /*@Override*/
      encode(e, t, n, r, i) {
        let s;
        switch (t) {
          case z.QR_CODE:
            s = new qt();
            break;
          default:
            throw new D("No encoder available for format " + t);
        }
        return s.encode(e, t, n, r, i);
      }
    }
    class Ct extends tn {
      constructor(e, t, n, r, i, s, o, a) {
        if (super(s, o), this.yuvData = e, this.dataWidth = t, this.dataHeight = n, this.left = r, this.top = i, r + s > t || i + o > n)
          throw new D("Crop rectangle does not fit within image data.");
        a && this.reverseHorizontal(s, o);
      }
      /*@Override*/
      getRow(e, t) {
        if (e < 0 || e >= this.getHeight())
          throw new D("Requested row is outside the image: " + e);
        const n = this.getWidth();
        (t == null || t.length < n) && (t = new Uint8ClampedArray(n));
        const r = (e + this.top) * this.dataWidth + this.left;
        return re.arraycopy(this.yuvData, r, t, 0, n), t;
      }
      /*@Override*/
      getMatrix() {
        const e = this.getWidth(), t = this.getHeight();
        if (e === this.dataWidth && t === this.dataHeight)
          return this.yuvData;
        const n = e * t, r = new Uint8ClampedArray(n);
        let i = this.top * this.dataWidth + this.left;
        if (e === this.dataWidth)
          return re.arraycopy(this.yuvData, i, r, 0, n), r;
        for (let s = 0; s < t; s++) {
          const o = s * e;
          re.arraycopy(this.yuvData, i, r, o, e), i += this.dataWidth;
        }
        return r;
      }
      /*@Override*/
      isCropSupported() {
        return !0;
      }
      /*@Override*/
      crop(e, t, n, r) {
        return new Ct(this.yuvData, this.dataWidth, this.dataHeight, this.left + e, this.top + t, n, r, !1);
      }
      renderThumbnail() {
        const e = this.getWidth() / Ct.THUMBNAIL_SCALE_FACTOR, t = this.getHeight() / Ct.THUMBNAIL_SCALE_FACTOR, n = new Int32Array(e * t), r = this.yuvData;
        let i = this.top * this.dataWidth + this.left;
        for (let s = 0; s < t; s++) {
          const o = s * e;
          for (let a = 0; a < e; a++) {
            const l = r[i + a * Ct.THUMBNAIL_SCALE_FACTOR] & 255;
            n[o + a] = 4278190080 | l * 65793;
          }
          i += this.dataWidth * Ct.THUMBNAIL_SCALE_FACTOR;
        }
        return n;
      }
      /**
       * @return width of image from {@link #renderThumbnail()}
       */
      getThumbnailWidth() {
        return this.getWidth() / Ct.THUMBNAIL_SCALE_FACTOR;
      }
      /**
       * @return height of image from {@link #renderThumbnail()}
       */
      getThumbnailHeight() {
        return this.getHeight() / Ct.THUMBNAIL_SCALE_FACTOR;
      }
      reverseHorizontal(e, t) {
        const n = this.yuvData;
        for (let r = 0, i = this.top * this.dataWidth + this.left; r < t; r++, i += this.dataWidth) {
          const s = i + e / 2;
          for (let o = i, a = i + e - 1; o < s; o++, a--) {
            const l = n[o];
            n[o] = n[a], n[a] = l;
          }
        }
      }
      invert() {
        return new Tt(this);
      }
    }
    Ct.THUMBNAIL_SCALE_FACTOR = 2;
    class Qn extends tn {
      constructor(e, t, n, r, i, s, o) {
        if (super(t, n), this.dataWidth = r, this.dataHeight = i, this.left = s, this.top = o, e.BYTES_PER_ELEMENT === 4) {
          const a = t * n, l = new Uint8ClampedArray(a);
          for (let c = 0; c < a; c++) {
            const f = e[c], d = f >> 16 & 255, C = f >> 7 & 510, p = f & 255;
            l[c] = /*(byte) */
            (d + C + p) / 4 & 255;
          }
          this.luminances = l;
        } else
          this.luminances = e;
        if (r === void 0 && (this.dataWidth = t), i === void 0 && (this.dataHeight = n), s === void 0 && (this.left = 0), o === void 0 && (this.top = 0), this.left + t > this.dataWidth || this.top + n > this.dataHeight)
          throw new D("Crop rectangle does not fit within image data.");
      }
      /*@Override*/
      getRow(e, t) {
        if (e < 0 || e >= this.getHeight())
          throw new D("Requested row is outside the image: " + e);
        const n = this.getWidth();
        (t == null || t.length < n) && (t = new Uint8ClampedArray(n));
        const r = (e + this.top) * this.dataWidth + this.left;
        return re.arraycopy(this.luminances, r, t, 0, n), t;
      }
      /*@Override*/
      getMatrix() {
        const e = this.getWidth(), t = this.getHeight();
        if (e === this.dataWidth && t === this.dataHeight)
          return this.luminances;
        const n = e * t, r = new Uint8ClampedArray(n);
        let i = this.top * this.dataWidth + this.left;
        if (e === this.dataWidth)
          return re.arraycopy(this.luminances, i, r, 0, n), r;
        for (let s = 0; s < t; s++) {
          const o = s * e;
          re.arraycopy(this.luminances, i, r, o, e), i += this.dataWidth;
        }
        return r;
      }
      /*@Override*/
      isCropSupported() {
        return !0;
      }
      /*@Override*/
      crop(e, t, n, r) {
        return new Qn(this.luminances, n, r, this.dataWidth, this.dataHeight, this.left + e, this.top + t);
      }
      invert() {
        return new Tt(this);
      }
    }
    class Ir extends k {
      static forName(e) {
        return this.getCharacterSetECIByName(e);
      }
    }
    class Jn {
    }
    Jn.ISO_8859_1 = k.ISO8859_1;
    class pr {
      /**
       * @return {@code true} if compact instead of full mode
       */
      isCompact() {
        return this.compact;
      }
      setCompact(e) {
        this.compact = e;
      }
      /**
       * @return size in pixels (width and height)
       */
      getSize() {
        return this.size;
      }
      setSize(e) {
        this.size = e;
      }
      /**
       * @return number of levels
       */
      getLayers() {
        return this.layers;
      }
      setLayers(e) {
        this.layers = e;
      }
      /**
       * @return number of data codewords
       */
      getCodeWords() {
        return this.codeWords;
      }
      setCodeWords(e) {
        this.codeWords = e;
      }
      /**
       * @return the symbol image
       */
      getMatrix() {
        return this.matrix;
      }
      setMatrix(e) {
        this.matrix = e;
      }
    }
    class br {
      /**
       * The singletonList(T) method is used to return an immutable list containing only the specified object.
       */
      static singletonList(e) {
        return [e];
      }
      /**
       * The min(Collection<? extends T>, Comparator<? super T>) method is used to return the minimum element of the given collection, according to the order induced by the specified comparator.
       */
      static min(e, t) {
        return e.sort(t)[0];
      }
    }
    class ui {
      constructor(e) {
        this.previous = e;
      }
      getPrevious() {
        return this.previous;
      }
    }
    class Qt extends ui {
      constructor(e, t, n) {
        super(e), this.value = t, this.bitCount = n;
      }
      /**
       * @Override
       */
      appendTo(e, t) {
        e.appendBits(this.value, this.bitCount);
      }
      add(e, t) {
        return new Qt(this, e, t);
      }
      addBinaryShift(e, t) {
        return console.warn("addBinaryShift on SimpleToken, this simply returns a copy of this token"), new Qt(this, e, t);
      }
      /**
       * @Override
       */
      toString() {
        let e = this.value & (1 << this.bitCount) - 1;
        return e |= 1 << this.bitCount, "<" + j.toBinaryString(e | 1 << this.bitCount).substring(1) + ">";
      }
    }
    class $n extends Qt {
      constructor(e, t, n) {
        super(e, 0, 0), this.binaryShiftStart = t, this.binaryShiftByteCount = n;
      }
      /**
       * @Override
       */
      appendTo(e, t) {
        for (let n = 0; n < this.binaryShiftByteCount; n++)
          (n === 0 || n === 31 && this.binaryShiftByteCount <= 62) && (e.appendBits(31, 5), this.binaryShiftByteCount > 62 ? e.appendBits(this.binaryShiftByteCount - 31, 16) : n === 0 ? e.appendBits(Math.min(this.binaryShiftByteCount, 31), 5) : e.appendBits(this.binaryShiftByteCount - 31, 5)), e.appendBits(t[this.binaryShiftStart + n], 8);
      }
      addBinaryShift(e, t) {
        return new $n(this, e, t);
      }
      /**
       * @Override
       */
      toString() {
        return "<" + this.binaryShiftStart + "::" + (this.binaryShiftStart + this.binaryShiftByteCount - 1) + ">";
      }
    }
    function fi(u, e, t) {
      return new $n(u, e, t);
    }
    function dn(u, e, t) {
      return new Qt(u, e, t);
    }
    const di = [
      "UPPER",
      "LOWER",
      "DIGIT",
      "MIXED",
      "PUNCT"
    ], vt = 0, _n = 1, ht = 2, yr = 3, mt = 4, xi = new Qt(null, 0, 0), er = [
      Int32Array.from([
        0,
        (5 << 16) + 28,
        (5 << 16) + 30,
        (5 << 16) + 29,
        656318
        // UPPER -> MIXED -> PUNCT
      ]),
      Int32Array.from([
        (9 << 16) + 480 + 14,
        0,
        (5 << 16) + 30,
        (5 << 16) + 29,
        656318
        // LOWER -> MIXED -> PUNCT
      ]),
      Int32Array.from([
        (4 << 16) + 14,
        (9 << 16) + 448 + 28,
        0,
        (9 << 16) + 448 + 29,
        932798
        // DIGIT -> UPPER -> MIXED -> PUNCT
      ]),
      Int32Array.from([
        (5 << 16) + 29,
        (5 << 16) + 28,
        656318,
        0,
        (5 << 16) + 30
        // MIXED -> PUNCT
      ]),
      Int32Array.from([
        (5 << 16) + 31,
        656380,
        656382,
        656381,
        0
      ])
    ];
    function gi(u) {
      for (let e of u)
        me.fill(e, -1);
      return u[vt][mt] = 0, u[_n][mt] = 0, u[_n][vt] = 28, u[yr][mt] = 0, u[ht][mt] = 0, u[ht][vt] = 15, u;
    }
    const Sr = gi(me.createInt32Array(6, 6));
    class It {
      constructor(e, t, n, r) {
        this.token = e, this.mode = t, this.binaryShiftByteCount = n, this.bitCount = r;
      }
      getMode() {
        return this.mode;
      }
      getToken() {
        return this.token;
      }
      getBinaryShiftByteCount() {
        return this.binaryShiftByteCount;
      }
      getBitCount() {
        return this.bitCount;
      }
      // Create a new state representing this state with a latch to a (not
      // necessary different) mode, and then a code.
      latchAndAppend(e, t) {
        let n = this.bitCount, r = this.token;
        if (e !== this.mode) {
          let s = er[this.mode][e];
          r = dn(r, s & 65535, s >> 16), n += s >> 16;
        }
        let i = e === ht ? 4 : 5;
        return r = dn(r, t, i), new It(r, e, 0, n + i);
      }
      // Create a new state representing this state, with a temporary shift
      // to a different mode to output a single value.
      shiftAndAppend(e, t) {
        let n = this.token, r = this.mode === ht ? 4 : 5;
        return n = dn(n, Sr[this.mode][e], r), n = dn(n, t, 5), new It(n, this.mode, 0, this.bitCount + r + 5);
      }
      // Create a new state representing this state, but an additional character
      // output in Binary Shift mode.
      addBinaryShiftChar(e) {
        let t = this.token, n = this.mode, r = this.bitCount;
        if (this.mode === mt || this.mode === ht) {
          let o = er[n][vt];
          t = dn(t, o & 65535, o >> 16), r += o >> 16, n = vt;
        }
        let i = this.binaryShiftByteCount === 0 || this.binaryShiftByteCount === 31 ? 18 : this.binaryShiftByteCount === 62 ? 9 : 8, s = new It(t, n, this.binaryShiftByteCount + 1, r + i);
        return s.binaryShiftByteCount === 2078 && (s = s.endBinaryShift(e + 1)), s;
      }
      // Create the state identical to this one, but we are no longer in
      // Binary Shift mode.
      endBinaryShift(e) {
        if (this.binaryShiftByteCount === 0)
          return this;
        let t = this.token;
        return t = fi(t, e - this.binaryShiftByteCount, this.binaryShiftByteCount), new It(t, this.mode, 0, this.bitCount);
      }
      // Returns true if "this" state is better (equal: or) to be in than "that"
      // state under all possible circumstances.
      isBetterThanOrEqualTo(e) {
        let t = this.bitCount + (er[this.mode][e.mode] >> 16);
        return this.binaryShiftByteCount < e.binaryShiftByteCount ? t += It.calculateBinaryShiftCost(e) - It.calculateBinaryShiftCost(this) : this.binaryShiftByteCount > e.binaryShiftByteCount && e.binaryShiftByteCount > 0 && (t += 10), t <= e.bitCount;
      }
      toBitArray(e) {
        let t = [];
        for (let r = this.endBinaryShift(e.length).token; r !== null; r = r.getPrevious())
          t.unshift(r);
        let n = new le();
        for (const r of t)
          r.appendTo(n, e);
        return n;
      }
      /**
       * @Override
       */
      toString() {
        return q.format("%s bits=%d bytes=%d", di[this.mode], this.bitCount, this.binaryShiftByteCount);
      }
      static calculateBinaryShiftCost(e) {
        return e.binaryShiftByteCount > 62 ? 21 : e.binaryShiftByteCount > 31 ? 20 : e.binaryShiftByteCount > 0 ? 10 : 0;
      }
    }
    It.INITIAL_STATE = new It(xi, vt, 0, 0);
    function wi(u) {
      const e = q.getCharCode(" "), t = q.getCharCode("."), n = q.getCharCode(",");
      u[vt][e] = 1;
      const r = q.getCharCode("Z"), i = q.getCharCode("A");
      for (let d = i; d <= r; d++)
        u[vt][d] = d - i + 2;
      u[_n][e] = 1;
      const s = q.getCharCode("z"), o = q.getCharCode("a");
      for (let d = o; d <= s; d++)
        u[_n][d] = d - o + 2;
      u[ht][e] = 1;
      const a = q.getCharCode("9"), l = q.getCharCode("0");
      for (let d = l; d <= a; d++)
        u[ht][d] = d - l + 2;
      u[ht][n] = 12, u[ht][t] = 13;
      const c = [
        "\0",
        " ",
        "",
        "",
        "",
        "",
        "",
        "",
        "\x07",
        "\b",
        "	",
        `
`,
        "\v",
        "\f",
        "\r",
        "\x1B",
        "",
        "",
        "",
        "",
        "@",
        "\\",
        "^",
        "_",
        "`",
        "|",
        "~",
        ""
      ];
      for (let d = 0; d < c.length; d++)
        u[yr][q.getCharCode(c[d])] = d;
      const f = [
        "\0",
        "\r",
        "\0",
        "\0",
        "\0",
        "\0",
        "!",
        "'",
        "#",
        "$",
        "%",
        "&",
        "'",
        "(",
        ")",
        "*",
        "+",
        ",",
        "-",
        ".",
        "/",
        ":",
        ";",
        "<",
        "=",
        ">",
        "?",
        "[",
        "]",
        "{",
        "}"
      ];
      for (let d = 0; d < f.length; d++)
        q.getCharCode(f[d]) > 0 && (u[mt][q.getCharCode(f[d])] = d);
      return u;
    }
    const tr = wi(me.createInt32Array(5, 256));
    class xn {
      constructor(e) {
        this.text = e;
      }
      /**
       * @return text represented by this encoder encoded as a {@link BitArray}
       */
      encode() {
        const e = q.getCharCode(" "), t = q.getCharCode(`
`);
        let n = br.singletonList(It.INITIAL_STATE);
        for (let i = 0; i < this.text.length; i++) {
          let s, o = i + 1 < this.text.length ? this.text[i + 1] : 0;
          switch (this.text[i]) {
            case q.getCharCode("\r"):
              s = o === t ? 2 : 0;
              break;
            case q.getCharCode("."):
              s = o === e ? 3 : 0;
              break;
            case q.getCharCode(","):
              s = o === e ? 4 : 0;
              break;
            case q.getCharCode(":"):
              s = o === e ? 5 : 0;
              break;
            default:
              s = 0;
          }
          s > 0 ? (n = xn.updateStateListForPair(n, i, s), i++) : n = this.updateStateListForChar(n, i);
        }
        return br.min(n, (i, s) => i.getBitCount() - s.getBitCount()).toBitArray(this.text);
      }
      // We update a set of states for a new character by updating each state
      // for the new character, merging the results, and then removing the
      // non-optimal states.
      updateStateListForChar(e, t) {
        const n = [];
        for (let r of e)
          this.updateStateForChar(r, t, n);
        return xn.simplifyStates(n);
      }
      // Return a set of states that represent the possible ways of updating this
      // state for the next character.  The resulting set of states are added to
      // the "result" list.
      updateStateForChar(e, t, n) {
        let r = this.text[t] & 255, i = tr[e.getMode()][r] > 0, s = null;
        for (let o = 0; o <= mt; o++) {
          let a = tr[o][r];
          if (a > 0) {
            if (s == null && (s = e.endBinaryShift(t)), !i || o === e.getMode() || o === ht) {
              const l = s.latchAndAppend(o, a);
              n.push(l);
            }
            if (!i && Sr[e.getMode()][o] >= 0) {
              const l = s.shiftAndAppend(o, a);
              n.push(l);
            }
          }
        }
        if (e.getBinaryShiftByteCount() > 0 || tr[e.getMode()][r] === 0) {
          let o = e.addBinaryShiftChar(t);
          n.push(o);
        }
      }
      static updateStateListForPair(e, t, n) {
        const r = [];
        for (let i of e)
          this.updateStateForPair(i, t, n, r);
        return this.simplifyStates(r);
      }
      static updateStateForPair(e, t, n, r) {
        let i = e.endBinaryShift(t);
        if (r.push(i.latchAndAppend(mt, n)), e.getMode() !== mt && r.push(i.shiftAndAppend(mt, n)), n === 3 || n === 4) {
          let s = i.latchAndAppend(ht, 16 - n).latchAndAppend(ht, 1);
          r.push(s);
        }
        if (e.getBinaryShiftByteCount() > 0) {
          let s = e.addBinaryShiftChar(t).addBinaryShiftChar(t + 1);
          r.push(s);
        }
      }
      static simplifyStates(e) {
        let t = [];
        for (const n of e) {
          let r = !0;
          for (const i of t) {
            if (i.isBetterThanOrEqualTo(n)) {
              r = !1;
              break;
            }
            n.isBetterThanOrEqualTo(i) && (t = t.filter((s) => s !== i));
          }
          r && t.push(n);
        }
        return t;
      }
    }
    class ae {
      constructor() {
      }
      /**
       * Encodes the given binary content as an Aztec symbol
       *
       * @param data input data string
       * @return Aztec symbol matrix with metadata
       */
      static encodeBytes(e) {
        return ae.encode(e, ae.DEFAULT_EC_PERCENT, ae.DEFAULT_AZTEC_LAYERS);
      }
      /**
       * Encodes the given binary content as an Aztec symbol
       *
       * @param data input data string
       * @param minECCPercent minimal percentage of error check words (According to ISO/IEC 24778:2008,
       *                      a minimum of 23% + 3 words is recommended)
       * @param userSpecifiedLayers if non-zero, a user-specified value for the number of layers
       * @return Aztec symbol matrix with metadata
       */
      static encode(e, t, n) {
        let r = new xn(e).encode(), i = j.truncDivision(r.getSize() * t, 100) + 11, s = r.getSize() + i, o, a, l, c, f;
        if (n !== ae.DEFAULT_AZTEC_LAYERS) {
          if (o = n < 0, a = Math.abs(n), a > (o ? ae.MAX_NB_BITS_COMPACT : ae.MAX_NB_BITS))
            throw new D(q.format("Illegal value %s for layers", n));
          l = ae.totalBitsInLayer(a, o), c = ae.WORD_SIZE[a];
          let L = l - l % c;
          if (f = ae.stuffBits(r, c), f.getSize() + i > L)
            throw new D("Data to large for user specified layer");
          if (o && f.getSize() > c * 64)
            throw new D("Data to large for user specified layer");
        } else {
          c = 0, f = null;
          for (let L = 0; ; L++) {
            if (L > ae.MAX_NB_BITS)
              throw new D("Data too large for an Aztec code");
            if (o = L <= 3, a = o ? L + 1 : L, l = ae.totalBitsInLayer(a, o), s > l)
              continue;
            (f == null || c !== ae.WORD_SIZE[a]) && (c = ae.WORD_SIZE[a], f = ae.stuffBits(r, c));
            let ie = l - l % c;
            if (!(o && f.getSize() > c * 64) && f.getSize() + i <= ie)
              break;
          }
        }
        let d = ae.generateCheckWords(f, l, c), C = f.getSize() / c, p = ae.generateModeMessage(o, a, C), b = (o ? 11 : 14) + a * 4, y = new Int32Array(b), _;
        if (o) {
          _ = b;
          for (let L = 0; L < y.length; L++)
            y[L] = L;
        } else {
          _ = b + 1 + 2 * j.truncDivision(j.truncDivision(b, 2) - 1, 15);
          let L = j.truncDivision(b, 2), ie = j.truncDivision(_, 2);
          for (let Q = 0; Q < L; Q++) {
            let rt = Q + j.truncDivision(Q, 15);
            y[L - Q - 1] = ie - rt - 1, y[L + Q] = ie + rt + 1;
          }
        }
        let P = new Fe(_);
        for (let L = 0, ie = 0; L < a; L++) {
          let Q = (a - L) * 4 + (o ? 9 : 12);
          for (let rt = 0; rt < Q; rt++) {
            let dt = rt * 2;
            for (let it = 0; it < 2; it++)
              d.get(ie + dt + it) && P.set(y[L * 2 + it], y[L * 2 + rt]), d.get(ie + Q * 2 + dt + it) && P.set(y[L * 2 + rt], y[b - 1 - L * 2 - it]), d.get(ie + Q * 4 + dt + it) && P.set(y[b - 1 - L * 2 - it], y[b - 1 - L * 2 - rt]), d.get(ie + Q * 6 + dt + it) && P.set(y[b - 1 - L * 2 - rt], y[L * 2 + it]);
          }
          ie += Q * 8;
        }
        if (ae.drawModeMessage(P, o, _, p), o)
          ae.drawBullsEye(P, j.truncDivision(_, 2), 5);
        else {
          ae.drawBullsEye(P, j.truncDivision(_, 2), 7);
          for (let L = 0, ie = 0; L < j.truncDivision(b, 2) - 1; L += 15, ie += 16)
            for (let Q = j.truncDivision(_, 2) & 1; Q < _; Q += 2)
              P.set(j.truncDivision(_, 2) - ie, Q), P.set(j.truncDivision(_, 2) + ie, Q), P.set(Q, j.truncDivision(_, 2) - ie), P.set(Q, j.truncDivision(_, 2) + ie);
        }
        let F = new pr();
        return F.setCompact(o), F.setSize(_), F.setLayers(a), F.setCodeWords(C), F.setMatrix(P), F;
      }
      static drawBullsEye(e, t, n) {
        for (let r = 0; r < n; r += 2)
          for (let i = t - r; i <= t + r; i++)
            e.set(i, t - r), e.set(i, t + r), e.set(t - r, i), e.set(t + r, i);
        e.set(t - n, t - n), e.set(t - n + 1, t - n), e.set(t - n, t - n + 1), e.set(t + n, t - n), e.set(t + n, t - n + 1), e.set(t + n, t + n - 1);
      }
      static generateModeMessage(e, t, n) {
        let r = new le();
        return e ? (r.appendBits(t - 1, 2), r.appendBits(n - 1, 6), r = ae.generateCheckWords(r, 28, 4)) : (r.appendBits(t - 1, 5), r.appendBits(n - 1, 11), r = ae.generateCheckWords(r, 40, 4)), r;
      }
      static drawModeMessage(e, t, n, r) {
        let i = j.truncDivision(n, 2);
        if (t)
          for (let s = 0; s < 7; s++) {
            let o = i - 3 + s;
            r.get(s) && e.set(o, i - 5), r.get(s + 7) && e.set(i + 5, o), r.get(20 - s) && e.set(o, i + 5), r.get(27 - s) && e.set(i - 5, o);
          }
        else
          for (let s = 0; s < 10; s++) {
            let o = i - 5 + s + j.truncDivision(s, 5);
            r.get(s) && e.set(o, i - 7), r.get(s + 10) && e.set(i + 7, o), r.get(29 - s) && e.set(o, i + 7), r.get(39 - s) && e.set(i - 7, o);
          }
      }
      static generateCheckWords(e, t, n) {
        let r = e.getSize() / n, i = new qn(ae.getGF(n)), s = j.truncDivision(t, n), o = ae.bitsToWords(e, n, s);
        i.encode(o, s - r);
        let a = t % n, l = new le();
        l.appendBits(0, a);
        for (const c of Array.from(o))
          l.appendBits(c, n);
        return l;
      }
      static bitsToWords(e, t, n) {
        let r = new Int32Array(n), i, s;
        for (i = 0, s = e.getSize() / t; i < s; i++) {
          let o = 0;
          for (let a = 0; a < t; a++)
            o |= e.get(i * t + a) ? 1 << t - a - 1 : 0;
          r[i] = o;
        }
        return r;
      }
      static getGF(e) {
        switch (e) {
          case 4:
            return ce.AZTEC_PARAM;
          case 6:
            return ce.AZTEC_DATA_6;
          case 8:
            return ce.AZTEC_DATA_8;
          case 10:
            return ce.AZTEC_DATA_10;
          case 12:
            return ce.AZTEC_DATA_12;
          default:
            throw new D("Unsupported word size " + e);
        }
      }
      static stuffBits(e, t) {
        let n = new le(), r = e.getSize(), i = (1 << t) - 2;
        for (let s = 0; s < r; s += t) {
          let o = 0;
          for (let a = 0; a < t; a++)
            (s + a >= r || e.get(s + a)) && (o |= 1 << t - 1 - a);
          (o & i) === i ? (n.appendBits(o & i, t), s--) : o & i ? n.appendBits(o, t) : (n.appendBits(o | 1, t), s--);
        }
        return n;
      }
      static totalBitsInLayer(e, t) {
        return ((t ? 88 : 112) + 16 * e) * e;
      }
    }
    ae.DEFAULT_EC_PERCENT = 33, ae.DEFAULT_AZTEC_LAYERS = 0, ae.MAX_NB_BITS = 32, ae.MAX_NB_BITS_COMPACT = 4, ae.WORD_SIZE = Int32Array.from([
      4,
      6,
      6,
      8,
      8,
      8,
      8,
      8,
      8,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      10,
      12,
      12,
      12,
      12,
      12,
      12,
      12,
      12,
      12,
      12
    ]);
    class On {
      // @Override
      encode(e, t, n, r) {
        return this.encodeWithHints(e, t, n, r, null);
      }
      // @Override
      encodeWithHints(e, t, n, r, i) {
        let s = Jn.ISO_8859_1, o = ae.DEFAULT_EC_PERCENT, a = ae.DEFAULT_AZTEC_LAYERS;
        return i != null && (i.has(Pe.CHARACTER_SET) && (s = Ir.forName(i.get(Pe.CHARACTER_SET).toString())), i.has(Pe.ERROR_CORRECTION) && (o = j.parseInt(i.get(Pe.ERROR_CORRECTION).toString())), i.has(Pe.AZTEC_LAYERS) && (a = j.parseInt(i.get(Pe.AZTEC_LAYERS).toString()))), On.encodeLayers(e, t, n, r, s, o, a);
      }
      static encodeLayers(e, t, n, r, i, s, o) {
        if (t !== z.AZTEC)
          throw new D("Can only encode AZTEC, but got " + t);
        let a = ae.encode(q.getBytes(e, i), s, o);
        return On.renderResult(a, n, r);
      }
      static renderResult(e, t, n) {
        let r = e.getMatrix();
        if (r == null)
          throw new gt();
        let i = r.getWidth(), s = r.getHeight(), o = Math.max(t, i), a = Math.max(n, s), l = Math.min(o / i, a / s), c = (o - i * l) / 2, f = (a - s * l) / 2, d = new Fe(o, a);
        for (let C = 0, p = f; C < s; C++, p += l)
          for (let b = 0, y = c; b < i; b++, y += l)
            r.get(b, C) && d.setRegion(y, p, l, l);
        return d;
      }
    }
    h.AbstractExpandedDecoder = Vn, h.ArgumentException = R, h.ArithmeticException = An, h.AztecCode = pr, h.AztecCodeReader = mn, h.AztecCodeWriter = On, h.AztecDecoder = Ie, h.AztecDetector = lr, h.AztecDetectorResult = or, h.AztecEncoder = ae, h.AztecHighLevelEncoder = xn, h.AztecPoint = qe, h.BarcodeFormat = z, h.Binarizer = Be, h.BinaryBitmap = ne, h.BitArray = le, h.BitMatrix = Fe, h.BitSource = Xn, h.BrowserAztecCodeReader = Hr, h.BrowserBarcodeReader = Zr, h.BrowserCodeReader = Lt, h.BrowserDatamatrixCodeReader = jr, h.BrowserMultiFormatReader = oi, h.BrowserPDF417Reader = ai, h.BrowserQRCodeReader = li, h.BrowserQRCodeSvgWriter = Gt, h.CharacterSetECI = k, h.ChecksumException = J, h.Code128Reader = V, h.Code39Reader = ve, h.DataMatrixDecodedBitStreamParser = Rt, h.DataMatrixReader = Dt, h.DecodeHintType = xe, h.DecoderResult = nn, h.DefaultGridSampler = ar, h.DetectorResult = Cn, h.EAN13Reader = Pt, h.EncodeHintType = Pe, h.Exception = E, h.FormatException = U, h.GenericGF = ce, h.GenericGFPoly = $e, h.GlobalHistogramBinarizer = ke, h.GridSampler = Pn, h.GridSamplerInstance = _t, h.HTMLCanvasElementLuminanceSource = Nt, h.HybridBinarizer = $, h.ITFReader = Ae, h.IllegalArgumentException = D, h.IllegalStateException = gt, h.InvertedLuminanceSource = Tt, h.LuminanceSource = tn, h.MathUtils = oe, h.MultiFormatOneDReader = Kt, h.MultiFormatReader = mr, h.MultiFormatWriter = hi, h.NotFoundException = O, h.OneDReader = Me, h.PDF417DecodedBitStreamParser = N, h.PDF417DecoderErrorCorrection = xr, h.PDF417Reader = Xe, h.PDF417ResultMetadata = wr, h.PerspectiveTransform = ut, h.PlanarYUVLuminanceSource = Ct, h.QRCodeByteMatrix = Nn, h.QRCodeDataMask = lt, h.QRCodeDecodedBitStreamParser = Se, h.QRCodeDecoderErrorCorrectionLevel = pe, h.QRCodeDecoderFormatInformation = Ze, h.QRCodeEncoder = be, h.QRCodeEncoderQRCode = Vt, h.QRCodeMaskUtil = Oe, h.QRCodeMatrixUtil = Z, h.QRCodeMode = Y, h.QRCodeReader = Mt, h.QRCodeVersion = X, h.QRCodeWriter = qt, h.RGBLuminanceSource = Qn, h.RSS14Reader = _e, h.RSSExpandedReader = v, h.ReaderException = Tn, h.ReedSolomonDecoder = sn, h.ReedSolomonEncoder = qn, h.ReedSolomonException = Xt, h.Result = je, h.ResultMetadataType = Ue, h.ResultPoint = W, h.StringUtils = q, h.UnsupportedOperationException = Wt, h.VideoInputDevice = sr, h.WhiteRectangleDetector = pt, h.WriterException = Ee, h.ZXingArrays = me, h.ZXingCharset = Ir, h.ZXingInteger = j, h.ZXingStandardCharsets = Jn, h.ZXingStringBuilder = ge, h.ZXingStringEncoding = Je, h.ZXingSystem = re, h.createAbstractExpandedDecoder = fr, Object.defineProperty(h, "__esModule", { value: !0 });
  });
})(rr, rr.exports);
var Te = rr.exports;
const bi = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null
}, Symbol.toStringTag, { value: "Module" }));
var Or = function() {
  function w(x, h, m) {
    if (this.formatMap = /* @__PURE__ */ new Map([
      [H.QR_CODE, Te.BarcodeFormat.QR_CODE],
      [H.AZTEC, Te.BarcodeFormat.AZTEC],
      [H.CODABAR, Te.BarcodeFormat.CODABAR],
      [H.CODE_39, Te.BarcodeFormat.CODE_39],
      [H.CODE_93, Te.BarcodeFormat.CODE_93],
      [
        H.CODE_128,
        Te.BarcodeFormat.CODE_128
      ],
      [
        H.DATA_MATRIX,
        Te.BarcodeFormat.DATA_MATRIX
      ],
      [
        H.MAXICODE,
        Te.BarcodeFormat.MAXICODE
      ],
      [H.ITF, Te.BarcodeFormat.ITF],
      [H.EAN_13, Te.BarcodeFormat.EAN_13],
      [H.EAN_8, Te.BarcodeFormat.EAN_8],
      [H.PDF_417, Te.BarcodeFormat.PDF_417],
      [H.RSS_14, Te.BarcodeFormat.RSS_14],
      [
        H.RSS_EXPANDED,
        Te.BarcodeFormat.RSS_EXPANDED
      ],
      [H.UPC_A, Te.BarcodeFormat.UPC_A],
      [H.UPC_E, Te.BarcodeFormat.UPC_E],
      [
        H.UPC_EAN_EXTENSION,
        Te.BarcodeFormat.UPC_EAN_EXTENSION
      ]
    ]), this.reverseFormatMap = this.createReverseFormatMap(), !bi)
      throw "Use html5qrcode.min.js without edit, ZXing not found.";
    this.verbose = h, this.logger = m;
    var I = this.createZXingFormats(x), g = /* @__PURE__ */ new Map();
    g.set(Te.DecodeHintType.POSSIBLE_FORMATS, I), g.set(Te.DecodeHintType.TRY_HARDER, !1), this.hints = g;
  }
  return w.prototype.decodeAsync = function(x) {
    var h = this;
    return new Promise(function(m, I) {
      try {
        m(h.decode(x));
      } catch (g) {
        I(g);
      }
    });
  }, w.prototype.decode = function(x) {
    var h = new Te.MultiFormatReader(this.verbose, this.hints), m = new Te.HTMLCanvasElementLuminanceSource(x), I = new Te.BinaryBitmap(new Te.HybridBinarizer(m)), g = h.decode(I);
    return {
      text: g.text,
      format: Ur.create(this.toHtml5QrcodeSupportedFormats(g.format)),
      debugData: this.createDebugData()
    };
  }, w.prototype.createReverseFormatMap = function() {
    var x = /* @__PURE__ */ new Map();
    return this.formatMap.forEach(function(h, m, I) {
      x.set(h, m);
    }), x;
  }, w.prototype.toHtml5QrcodeSupportedFormats = function(x) {
    if (!this.reverseFormatMap.has(x))
      throw "reverseFormatMap doesn't have ".concat(x);
    return this.reverseFormatMap.get(x);
  }, w.prototype.createZXingFormats = function(x) {
    for (var h = [], m = 0, I = x; m < I.length; m++) {
      var g = I[m];
      this.formatMap.has(g) ? h.push(this.formatMap.get(g)) : this.logger.logError("".concat(g, " is not supported by") + "ZXingHtml5QrcodeShim");
    }
    return h;
  }, w.prototype.createDebugData = function() {
    return { decoderName: "zxing-js" };
  }, w;
}(), yi = function(w, x, h, m) {
  function I(g) {
    return g instanceof h ? g : new h(function(T) {
      T(g);
    });
  }
  return new (h || (h = Promise))(function(g, T) {
    function M(R) {
      try {
        E(m.next(R));
      } catch (D) {
        T(D);
      }
    }
    function B(R) {
      try {
        E(m.throw(R));
      } catch (D) {
        T(D);
      }
    }
    function E(R) {
      R.done ? g(R.value) : I(R.value).then(M, B);
    }
    E((m = m.apply(w, x || [])).next());
  });
}, Si = function(w, x) {
  var h = { label: 0, sent: function() {
    if (g[0] & 1) throw g[1];
    return g[1];
  }, trys: [], ops: [] }, m, I, g, T;
  return T = { next: M(0), throw: M(1), return: M(2) }, typeof Symbol == "function" && (T[Symbol.iterator] = function() {
    return this;
  }), T;
  function M(E) {
    return function(R) {
      return B([E, R]);
    };
  }
  function B(E) {
    if (m) throw new TypeError("Generator is already executing.");
    for (; T && (T = 0, E[0] && (h = 0)), h; ) try {
      if (m = 1, I && (g = E[0] & 2 ? I.return : E[0] ? I.throw || ((g = I.return) && g.call(I), 0) : I.next) && !(g = g.call(I, E[1])).done) return g;
      switch (I = 0, g && (E = [E[0] & 2, g.value]), E[0]) {
        case 0:
        case 1:
          g = E;
          break;
        case 4:
          return h.label++, { value: E[1], done: !1 };
        case 5:
          h.label++, I = E[1], E = [0];
          continue;
        case 7:
          E = h.ops.pop(), h.trys.pop();
          continue;
        default:
          if (g = h.trys, !(g = g.length > 0 && g[g.length - 1]) && (E[0] === 6 || E[0] === 2)) {
            h = 0;
            continue;
          }
          if (E[0] === 3 && (!g || E[1] > g[0] && E[1] < g[3])) {
            h.label = E[1];
            break;
          }
          if (E[0] === 6 && h.label < g[1]) {
            h.label = g[1], g = E;
            break;
          }
          if (g && h.label < g[2]) {
            h.label = g[2], h.ops.push(E);
            break;
          }
          g[2] && h.ops.pop(), h.trys.pop();
          continue;
      }
      E = x.call(w, h);
    } catch (R) {
      E = [6, R], I = 0;
    } finally {
      m = g = 0;
    }
    if (E[0] & 5) throw E[1];
    return { value: E[0] ? E[1] : void 0, done: !0 };
  }
}, Rr = function() {
  function w(x, h, m) {
    if (this.formatMap = /* @__PURE__ */ new Map([
      [H.QR_CODE, "qr_code"],
      [H.AZTEC, "aztec"],
      [H.CODABAR, "codabar"],
      [H.CODE_39, "code_39"],
      [H.CODE_93, "code_93"],
      [H.CODE_128, "code_128"],
      [H.DATA_MATRIX, "data_matrix"],
      [H.ITF, "itf"],
      [H.EAN_13, "ean_13"],
      [H.EAN_8, "ean_8"],
      [H.PDF_417, "pdf417"],
      [H.UPC_A, "upc_a"],
      [H.UPC_E, "upc_e"]
    ]), this.reverseFormatMap = this.createReverseFormatMap(), !w.isSupported())
      throw "Use html5qrcode.min.js without edit, Use BarcodeDetectorDelegate only if it isSupported();";
    this.verbose = h, this.logger = m;
    var I = this.createBarcodeDetectorFormats(x);
    if (this.detector = new BarcodeDetector(I), !this.detector)
      throw "BarcodeDetector detector not supported";
  }
  return w.isSupported = function() {
    if (!("BarcodeDetector" in window))
      return !1;
    var x = new BarcodeDetector({ formats: ["qr_code"] });
    return typeof x < "u";
  }, w.prototype.decodeAsync = function(x) {
    return yi(this, void 0, void 0, function() {
      var h, m;
      return Si(this, function(I) {
        switch (I.label) {
          case 0:
            return [4, this.detector.detect(x)];
          case 1:
            if (h = I.sent(), !h || h.length === 0)
              throw "No barcode or QR code detected.";
            return m = this.selectLargestBarcode(h), [2, {
              text: m.rawValue,
              format: Ur.create(this.toHtml5QrcodeSupportedFormats(m.format)),
              debugData: this.createDebugData()
            }];
        }
      });
    });
  }, w.prototype.selectLargestBarcode = function(x) {
    for (var h = null, m = 0, I = 0, g = x; I < g.length; I++) {
      var T = g[I], M = T.boundingBox.width * T.boundingBox.height;
      M > m && (m = M, h = T);
    }
    if (!h)
      throw "No largest barcode found";
    return h;
  }, w.prototype.createBarcodeDetectorFormats = function(x) {
    for (var h = [], m = 0, I = x; m < I.length; m++) {
      var g = I[m];
      this.formatMap.has(g) ? h.push(this.formatMap.get(g)) : this.logger.warn("".concat(g, " is not supported by") + "BarcodeDetectorDelegate");
    }
    return { formats: h };
  }, w.prototype.toHtml5QrcodeSupportedFormats = function(x) {
    if (!this.reverseFormatMap.has(x))
      throw "reverseFormatMap doesn't have ".concat(x);
    return this.reverseFormatMap.get(x);
  }, w.prototype.createReverseFormatMap = function() {
    var x = /* @__PURE__ */ new Map();
    return this.formatMap.forEach(function(h, m, I) {
      x.set(h, m);
    }), x;
  }, w.prototype.createDebugData = function() {
    return { decoderName: "BarcodeDetector" };
  }, w;
}(), Dr = function(w, x, h, m) {
  function I(g) {
    return g instanceof h ? g : new h(function(T) {
      T(g);
    });
  }
  return new (h || (h = Promise))(function(g, T) {
    function M(R) {
      try {
        E(m.next(R));
      } catch (D) {
        T(D);
      }
    }
    function B(R) {
      try {
        E(m.throw(R));
      } catch (D) {
        T(D);
      }
    }
    function E(R) {
      R.done ? g(R.value) : I(R.value).then(M, B);
    }
    E((m = m.apply(w, x || [])).next());
  });
}, Mr = function(w, x) {
  var h = { label: 0, sent: function() {
    if (g[0] & 1) throw g[1];
    return g[1];
  }, trys: [], ops: [] }, m, I, g, T;
  return T = { next: M(0), throw: M(1), return: M(2) }, typeof Symbol == "function" && (T[Symbol.iterator] = function() {
    return this;
  }), T;
  function M(E) {
    return function(R) {
      return B([E, R]);
    };
  }
  function B(E) {
    if (m) throw new TypeError("Generator is already executing.");
    for (; T && (T = 0, E[0] && (h = 0)), h; ) try {
      if (m = 1, I && (g = E[0] & 2 ? I.return : E[0] ? I.throw || ((g = I.return) && g.call(I), 0) : I.next) && !(g = g.call(I, E[1])).done) return g;
      switch (I = 0, g && (E = [E[0] & 2, g.value]), E[0]) {
        case 0:
        case 1:
          g = E;
          break;
        case 4:
          return h.label++, { value: E[1], done: !1 };
        case 5:
          h.label++, I = E[1], E = [0];
          continue;
        case 7:
          E = h.ops.pop(), h.trys.pop();
          continue;
        default:
          if (g = h.trys, !(g = g.length > 0 && g[g.length - 1]) && (E[0] === 6 || E[0] === 2)) {
            h = 0;
            continue;
          }
          if (E[0] === 3 && (!g || E[1] > g[0] && E[1] < g[3])) {
            h.label = E[1];
            break;
          }
          if (E[0] === 6 && h.label < g[1]) {
            h.label = g[1], g = E;
            break;
          }
          if (g && h.label < g[2]) {
            h.label = g[2], h.ops.push(E);
            break;
          }
          g[2] && h.ops.pop(), h.trys.pop();
          continue;
      }
      E = x.call(w, h);
    } catch (R) {
      E = [6, R], I = 0;
    } finally {
      m = g = 0;
    }
    if (E[0] & 5) throw E[1];
    return { value: E[0] ? E[1] : void 0, done: !0 };
  }
}, Ti = function() {
  function w(x, h, m, I) {
    this.EXECUTIONS_TO_REPORT_PERFORMANCE = 100, this.executions = 0, this.executionResults = [], this.wasPrimaryDecoderUsedInLastDecode = !1, this.verbose = m, h && Rr.isSupported() ? (this.primaryDecoder = new Rr(x, m, I), this.secondaryDecoder = new Or(x, m, I)) : this.primaryDecoder = new Or(x, m, I);
  }
  return w.prototype.decodeAsync = function(x) {
    return Dr(this, void 0, void 0, function() {
      var h;
      return Mr(this, function(m) {
        switch (m.label) {
          case 0:
            h = performance.now(), m.label = 1;
          case 1:
            return m.trys.push([1, , 3, 4]), [4, this.getDecoder().decodeAsync(x)];
          case 2:
            return [2, m.sent()];
          case 3:
            return this.possiblyLogPerformance(h), [7];
          case 4:
            return [2];
        }
      });
    });
  }, w.prototype.decodeRobustlyAsync = function(x) {
    return Dr(this, void 0, void 0, function() {
      var h, m;
      return Mr(this, function(I) {
        switch (I.label) {
          case 0:
            h = performance.now(), I.label = 1;
          case 1:
            return I.trys.push([1, 3, 4, 5]), [4, this.primaryDecoder.decodeAsync(x)];
          case 2:
            return [2, I.sent()];
          case 3:
            if (m = I.sent(), this.secondaryDecoder)
              return [2, this.secondaryDecoder.decodeAsync(x)];
            throw m;
          case 4:
            return this.possiblyLogPerformance(h), [7];
          case 5:
            return [2];
        }
      });
    });
  }, w.prototype.getDecoder = function() {
    return this.secondaryDecoder ? this.wasPrimaryDecoderUsedInLastDecode === !1 ? (this.wasPrimaryDecoderUsedInLastDecode = !0, this.primaryDecoder) : (this.wasPrimaryDecoderUsedInLastDecode = !1, this.secondaryDecoder) : this.primaryDecoder;
  }, w.prototype.possiblyLogPerformance = function(x) {
    if (this.verbose) {
      var h = performance.now() - x;
      this.executionResults.push(h), this.executions++, this.possiblyFlushPerformanceReport();
    }
  }, w.prototype.possiblyFlushPerformanceReport = function() {
    if (!(this.executions < this.EXECUTIONS_TO_REPORT_PERFORMANCE)) {
      for (var x = 0, h = 0, m = this.executionResults; h < m.length; h++) {
        var I = m[h];
        x += I;
      }
      var g = x / this.executionResults.length;
      console.log("".concat(g, " ms for ").concat(this.executionResults.length, " last runs.")), this.executions = 0, this.executionResults = [];
    }
  }, w;
}(), ir = /* @__PURE__ */ function() {
  var w = function(x, h) {
    return w = Object.setPrototypeOf || { __proto__: [] } instanceof Array && function(m, I) {
      m.__proto__ = I;
    } || function(m, I) {
      for (var g in I) Object.prototype.hasOwnProperty.call(I, g) && (m[g] = I[g]);
    }, w(x, h);
  };
  return function(x, h) {
    if (typeof h != "function" && h !== null)
      throw new TypeError("Class extends value " + String(h) + " is not a constructor or null");
    w(x, h);
    function m() {
      this.constructor = x;
    }
    x.prototype = h === null ? Object.create(h) : (m.prototype = h.prototype, new m());
  };
}(), Dn = function(w, x, h, m) {
  function I(g) {
    return g instanceof h ? g : new h(function(T) {
      T(g);
    });
  }
  return new (h || (h = Promise))(function(g, T) {
    function M(R) {
      try {
        E(m.next(R));
      } catch (D) {
        T(D);
      }
    }
    function B(R) {
      try {
        E(m.throw(R));
      } catch (D) {
        T(D);
      }
    }
    function E(R) {
      R.done ? g(R.value) : I(R.value).then(M, B);
    }
    E((m = m.apply(w, x || [])).next());
  });
}, Mn = function(w, x) {
  var h = { label: 0, sent: function() {
    if (g[0] & 1) throw g[1];
    return g[1];
  }, trys: [], ops: [] }, m, I, g, T;
  return T = { next: M(0), throw: M(1), return: M(2) }, typeof Symbol == "function" && (T[Symbol.iterator] = function() {
    return this;
  }), T;
  function M(E) {
    return function(R) {
      return B([E, R]);
    };
  }
  function B(E) {
    if (m) throw new TypeError("Generator is already executing.");
    for (; T && (T = 0, E[0] && (h = 0)), h; ) try {
      if (m = 1, I && (g = E[0] & 2 ? I.return : E[0] ? I.throw || ((g = I.return) && g.call(I), 0) : I.next) && !(g = g.call(I, E[1])).done) return g;
      switch (I = 0, g && (E = [E[0] & 2, g.value]), E[0]) {
        case 0:
        case 1:
          g = E;
          break;
        case 4:
          return h.label++, { value: E[1], done: !1 };
        case 5:
          h.label++, I = E[1], E = [0];
          continue;
        case 7:
          E = h.ops.pop(), h.trys.pop();
          continue;
        default:
          if (g = h.trys, !(g = g.length > 0 && g[g.length - 1]) && (E[0] === 6 || E[0] === 2)) {
            h = 0;
            continue;
          }
          if (E[0] === 3 && (!g || E[1] > g[0] && E[1] < g[3])) {
            h.label = E[1];
            break;
          }
          if (E[0] === 6 && h.label < g[1]) {
            h.label = g[1], g = E;
            break;
          }
          if (g && h.label < g[2]) {
            h.label = g[2], h.ops.push(E);
            break;
          }
          g[2] && h.ops.pop(), h.trys.pop();
          continue;
      }
      E = x.call(w, h);
    } catch (R) {
      E = [6, R], I = 0;
    } finally {
      m = g = 0;
    }
    if (E[0] & 5) throw E[1];
    return { value: E[0] ? E[1] : void 0, done: !0 };
  }
}, Gr = function() {
  function w(x, h) {
    this.name = x, this.track = h;
  }
  return w.prototype.isSupported = function() {
    return this.track.getCapabilities ? this.name in this.track.getCapabilities() : !1;
  }, w.prototype.apply = function(x) {
    var h = {};
    h[this.name] = x;
    var m = { advanced: [h] };
    return this.track.applyConstraints(m);
  }, w.prototype.value = function() {
    var x = this.track.getSettings();
    if (this.name in x) {
      var h = x[this.name];
      return h;
    }
    return null;
  }, w;
}(), Ni = function(w) {
  ir(x, w);
  function x(h, m) {
    return w.call(this, h, m) || this;
  }
  return x.prototype.min = function() {
    return this.getCapabilities().min;
  }, x.prototype.max = function() {
    return this.getCapabilities().max;
  }, x.prototype.step = function() {
    return this.getCapabilities().step;
  }, x.prototype.apply = function(h) {
    var m = {};
    m[this.name] = h;
    var I = { advanced: [m] };
    return this.track.applyConstraints(I);
  }, x.prototype.getCapabilities = function() {
    this.failIfNotSupported();
    var h = this.track.getCapabilities(), m = h[this.name];
    return {
      min: m.min,
      max: m.max,
      step: m.step
    };
  }, x.prototype.failIfNotSupported = function() {
    if (!this.isSupported())
      throw new Error("".concat(this.name, " capability not supported"));
  }, x;
}(Gr), _i = function(w) {
  ir(x, w);
  function x(h) {
    return w.call(this, "zoom", h) || this;
  }
  return x;
}(Ni), Oi = function(w) {
  ir(x, w);
  function x(h) {
    return w.call(this, "torch", h) || this;
  }
  return x;
}(Gr), Ri = function() {
  function w(x) {
    this.track = x;
  }
  return w.prototype.zoomFeature = function() {
    return new _i(this.track);
  }, w.prototype.torchFeature = function() {
    return new Oi(this.track);
  }, w;
}(), Di = function() {
  function w(x, h, m) {
    this.isClosed = !1, this.parentElement = x, this.mediaStream = h, this.callbacks = m, this.surface = this.createVideoElement(this.parentElement.clientWidth), x.append(this.surface);
  }
  return w.prototype.createVideoElement = function(x) {
    var h = document.createElement("video");
    return h.style.width = "".concat(x, "px"), h.style.display = "block", h.muted = !0, h.setAttribute("muted", "true"), h.playsInline = !0, h;
  }, w.prototype.setupSurface = function() {
    var x = this;
    this.surface.onabort = function() {
      throw "RenderedCameraImpl video surface onabort() called";
    }, this.surface.onerror = function() {
      throw "RenderedCameraImpl video surface onerror() called";
    };
    var h = function() {
      var m = x.surface.clientWidth, I = x.surface.clientHeight;
      x.callbacks.onRenderSurfaceReady(m, I), x.surface.removeEventListener("playing", h);
    };
    this.surface.addEventListener("playing", h), this.surface.srcObject = this.mediaStream, this.surface.play();
  }, w.create = function(x, h, m, I) {
    return Dn(this, void 0, void 0, function() {
      var g, T;
      return Mn(this, function(M) {
        switch (M.label) {
          case 0:
            return g = new w(x, h, I), m.aspectRatio ? (T = {
              aspectRatio: m.aspectRatio
            }, [4, g.getFirstTrackOrFail().applyConstraints(T)]) : [3, 2];
          case 1:
            M.sent(), M.label = 2;
          case 2:
            return g.setupSurface(), [2, g];
        }
      });
    });
  }, w.prototype.failIfClosed = function() {
    if (this.isClosed)
      throw "The RenderedCamera has already been closed.";
  }, w.prototype.getFirstTrackOrFail = function() {
    if (this.failIfClosed(), this.mediaStream.getVideoTracks().length === 0)
      throw "No video tracks found";
    return this.mediaStream.getVideoTracks()[0];
  }, w.prototype.pause = function() {
    this.failIfClosed(), this.surface.pause();
  }, w.prototype.resume = function(x) {
    this.failIfClosed();
    var h = this, m = function() {
      setTimeout(x, 200), h.surface.removeEventListener("playing", m);
    };
    this.surface.addEventListener("playing", m), this.surface.play();
  }, w.prototype.isPaused = function() {
    return this.failIfClosed(), this.surface.paused;
  }, w.prototype.getSurface = function() {
    return this.failIfClosed(), this.surface;
  }, w.prototype.getRunningTrackCapabilities = function() {
    return this.getFirstTrackOrFail().getCapabilities();
  }, w.prototype.getRunningTrackSettings = function() {
    return this.getFirstTrackOrFail().getSettings();
  }, w.prototype.applyVideoConstraints = function(x) {
    return Dn(this, void 0, void 0, function() {
      return Mn(this, function(h) {
        if ("aspectRatio" in x)
          throw "Changing 'aspectRatio' in run-time is not yet supported.";
        return [2, this.getFirstTrackOrFail().applyConstraints(x)];
      });
    });
  }, w.prototype.close = function() {
    if (this.isClosed)
      return Promise.resolve();
    var x = this;
    return new Promise(function(h, m) {
      var I = x.mediaStream.getVideoTracks(), g = I.length, T = 0;
      x.mediaStream.getVideoTracks().forEach(function(M) {
        x.mediaStream.removeTrack(M), M.stop(), ++T, T >= g && (x.isClosed = !0, x.parentElement.removeChild(x.surface), h());
      });
    });
  }, w.prototype.getCapabilities = function() {
    return new Ri(this.getFirstTrackOrFail());
  }, w;
}(), Mi = function() {
  function w(x) {
    this.mediaStream = x;
  }
  return w.prototype.render = function(x, h, m) {
    return Dn(this, void 0, void 0, function() {
      return Mn(this, function(I) {
        return [2, Di.create(x, this.mediaStream, h, m)];
      });
    });
  }, w.create = function(x) {
    return Dn(this, void 0, void 0, function() {
      var h, m;
      return Mn(this, function(I) {
        switch (I.label) {
          case 0:
            if (!navigator.mediaDevices)
              throw "navigator.mediaDevices not supported";
            return h = {
              audio: !1,
              video: x
            }, [4, navigator.mediaDevices.getUserMedia(h)];
          case 1:
            return m = I.sent(), [2, new w(m)];
        }
      });
    });
  }, w;
}(), Br = function(w, x, h, m) {
  function I(g) {
    return g instanceof h ? g : new h(function(T) {
      T(g);
    });
  }
  return new (h || (h = Promise))(function(g, T) {
    function M(R) {
      try {
        E(m.next(R));
      } catch (D) {
        T(D);
      }
    }
    function B(R) {
      try {
        E(m.throw(R));
      } catch (D) {
        T(D);
      }
    }
    function E(R) {
      R.done ? g(R.value) : I(R.value).then(M, B);
    }
    E((m = m.apply(w, x || [])).next());
  });
}, vr = function(w, x) {
  var h = { label: 0, sent: function() {
    if (g[0] & 1) throw g[1];
    return g[1];
  }, trys: [], ops: [] }, m, I, g, T;
  return T = { next: M(0), throw: M(1), return: M(2) }, typeof Symbol == "function" && (T[Symbol.iterator] = function() {
    return this;
  }), T;
  function M(E) {
    return function(R) {
      return B([E, R]);
    };
  }
  function B(E) {
    if (m) throw new TypeError("Generator is already executing.");
    for (; T && (T = 0, E[0] && (h = 0)), h; ) try {
      if (m = 1, I && (g = E[0] & 2 ? I.return : E[0] ? I.throw || ((g = I.return) && g.call(I), 0) : I.next) && !(g = g.call(I, E[1])).done) return g;
      switch (I = 0, g && (E = [E[0] & 2, g.value]), E[0]) {
        case 0:
        case 1:
          g = E;
          break;
        case 4:
          return h.label++, { value: E[1], done: !1 };
        case 5:
          h.label++, I = E[1], E = [0];
          continue;
        case 7:
          E = h.ops.pop(), h.trys.pop();
          continue;
        default:
          if (g = h.trys, !(g = g.length > 0 && g[g.length - 1]) && (E[0] === 6 || E[0] === 2)) {
            h = 0;
            continue;
          }
          if (E[0] === 3 && (!g || E[1] > g[0] && E[1] < g[3])) {
            h.label = E[1];
            break;
          }
          if (E[0] === 6 && h.label < g[1]) {
            h.label = g[1], g = E;
            break;
          }
          if (g && h.label < g[2]) {
            h.label = g[2], h.ops.push(E);
            break;
          }
          g[2] && h.ops.pop(), h.trys.pop();
          continue;
      }
      E = x.call(w, h);
    } catch (R) {
      E = [6, R], I = 0;
    } finally {
      m = g = 0;
    }
    if (E[0] & 5) throw E[1];
    return { value: E[0] ? E[1] : void 0, done: !0 };
  }
}, Bi = function() {
  function w() {
  }
  return w.failIfNotSupported = function() {
    return Br(this, void 0, void 0, function() {
      return vr(this, function(x) {
        if (!navigator.mediaDevices)
          throw "navigator.mediaDevices not supported";
        return [2, new w()];
      });
    });
  }, w.prototype.create = function(x) {
    return Br(this, void 0, void 0, function() {
      return vr(this, function(h) {
        return [2, Mi.create(x)];
      });
    });
  }, w;
}(), vi = function(w, x, h, m) {
  function I(g) {
    return g instanceof h ? g : new h(function(T) {
      T(g);
    });
  }
  return new (h || (h = Promise))(function(g, T) {
    function M(R) {
      try {
        E(m.next(R));
      } catch (D) {
        T(D);
      }
    }
    function B(R) {
      try {
        E(m.throw(R));
      } catch (D) {
        T(D);
      }
    }
    function E(R) {
      R.done ? g(R.value) : I(R.value).then(M, B);
    }
    E((m = m.apply(w, x || [])).next());
  });
}, Li = function(w, x) {
  var h = { label: 0, sent: function() {
    if (g[0] & 1) throw g[1];
    return g[1];
  }, trys: [], ops: [] }, m, I, g, T;
  return T = { next: M(0), throw: M(1), return: M(2) }, typeof Symbol == "function" && (T[Symbol.iterator] = function() {
    return this;
  }), T;
  function M(E) {
    return function(R) {
      return B([E, R]);
    };
  }
  function B(E) {
    if (m) throw new TypeError("Generator is already executing.");
    for (; T && (T = 0, E[0] && (h = 0)), h; ) try {
      if (m = 1, I && (g = E[0] & 2 ? I.return : E[0] ? I.throw || ((g = I.return) && g.call(I), 0) : I.next) && !(g = g.call(I, E[1])).done) return g;
      switch (I = 0, g && (E = [E[0] & 2, g.value]), E[0]) {
        case 0:
        case 1:
          g = E;
          break;
        case 4:
          return h.label++, { value: E[1], done: !1 };
        case 5:
          h.label++, I = E[1], E = [0];
          continue;
        case 7:
          E = h.ops.pop(), h.trys.pop();
          continue;
        default:
          if (g = h.trys, !(g = g.length > 0 && g[g.length - 1]) && (E[0] === 6 || E[0] === 2)) {
            h = 0;
            continue;
          }
          if (E[0] === 3 && (!g || E[1] > g[0] && E[1] < g[3])) {
            h.label = E[1];
            break;
          }
          if (E[0] === 6 && h.label < g[1]) {
            h.label = g[1], g = E;
            break;
          }
          if (g && h.label < g[2]) {
            h.label = g[2], h.ops.push(E);
            break;
          }
          g[2] && h.ops.pop(), h.trys.pop();
          continue;
      }
      E = x.call(w, h);
    } catch (R) {
      E = [6, R], I = 0;
    } finally {
      m = g = 0;
    }
    if (E[0] & 5) throw E[1];
    return { value: E[0] ? E[1] : void 0, done: !0 };
  }
}, Pi = function() {
  function w() {
  }
  return w.retrieve = function() {
    if (navigator.mediaDevices)
      return w.getCamerasFromMediaDevices();
    var x = MediaStreamTrack;
    return MediaStreamTrack && x.getSources ? w.getCamerasFromMediaStreamTrack() : w.rejectWithError();
  }, w.rejectWithError = function() {
    var x = en.unableToQuerySupportedDevices();
    return w.isHttpsOrLocalhost() || (x = en.insecureContextCameraQueryError()), Promise.reject(x);
  }, w.isHttpsOrLocalhost = function() {
    if (location.protocol === "https:")
      return !0;
    var x = location.host.split(":")[0];
    return x === "127.0.0.1" || x === "localhost";
  }, w.getCamerasFromMediaDevices = function() {
    return vi(this, void 0, void 0, function() {
      var x, h, m, I, g, T, M;
      return Li(this, function(B) {
        switch (B.label) {
          case 0:
            return x = function(E) {
              for (var R = E.getVideoTracks(), D = 0, ne = R; D < ne.length; D++) {
                var J = ne[D];
                J.enabled = !1, J.stop(), E.removeTrack(J);
              }
            }, [4, navigator.mediaDevices.getUserMedia({ audio: !1, video: !0 })];
          case 1:
            return h = B.sent(), [4, navigator.mediaDevices.enumerateDevices()];
          case 2:
            for (m = B.sent(), I = [], g = 0, T = m; g < T.length; g++)
              M = T[g], M.kind === "videoinput" && I.push({
                id: M.deviceId,
                label: M.label
              });
            return x(h), [2, I];
        }
      });
    });
  }, w.getCamerasFromMediaStreamTrack = function() {
    return new Promise(function(x, h) {
      var m = function(g) {
        for (var T = [], M = 0, B = g; M < B.length; M++) {
          var E = B[M];
          E.kind === "video" && T.push({
            id: E.id,
            label: E.label
          });
        }
        x(T);
      }, I = MediaStreamTrack;
      I.getSources(m);
    });
  }, w;
}(), Re;
(function(w) {
  w[w.UNKNOWN = 0] = "UNKNOWN", w[w.NOT_STARTED = 1] = "NOT_STARTED", w[w.SCANNING = 2] = "SCANNING", w[w.PAUSED = 3] = "PAUSED";
})(Re || (Re = {}));
var Fi = function() {
  function w() {
    this.state = Re.NOT_STARTED, this.onGoingTransactionNewState = Re.UNKNOWN;
  }
  return w.prototype.directTransition = function(x) {
    this.failIfTransitionOngoing(), this.validateTransition(x), this.state = x;
  }, w.prototype.startTransition = function(x) {
    return this.failIfTransitionOngoing(), this.validateTransition(x), this.onGoingTransactionNewState = x, this;
  }, w.prototype.execute = function() {
    if (this.onGoingTransactionNewState === Re.UNKNOWN)
      throw "Transaction is already cancelled, cannot execute().";
    var x = this.onGoingTransactionNewState;
    this.onGoingTransactionNewState = Re.UNKNOWN, this.directTransition(x);
  }, w.prototype.cancel = function() {
    if (this.onGoingTransactionNewState === Re.UNKNOWN)
      throw "Transaction is already cancelled, cannot cancel().";
    this.onGoingTransactionNewState = Re.UNKNOWN;
  }, w.prototype.getState = function() {
    return this.state;
  }, w.prototype.failIfTransitionOngoing = function() {
    if (this.onGoingTransactionNewState !== Re.UNKNOWN)
      throw "Cannot transition to a new state, already under transition";
  }, w.prototype.validateTransition = function(x) {
    switch (this.state) {
      case Re.UNKNOWN:
        throw "Transition from unknown is not allowed";
      case Re.NOT_STARTED:
        this.failIfNewStateIs(x, [Re.PAUSED]);
        break;
      case Re.SCANNING:
        break;
      case Re.PAUSED:
        break;
    }
  }, w.prototype.failIfNewStateIs = function(x, h) {
    for (var m = 0, I = h; m < I.length; m++) {
      var g = I[m];
      if (x === g)
        throw "Cannot transition from ".concat(this.state, " to ").concat(x);
    }
  }, w;
}(), ki = function() {
  function w(x) {
    this.stateManager = x;
  }
  return w.prototype.startTransition = function(x) {
    return this.stateManager.startTransition(x);
  }, w.prototype.directTransition = function(x) {
    this.stateManager.directTransition(x);
  }, w.prototype.getState = function() {
    return this.stateManager.getState();
  }, w.prototype.canScanFile = function() {
    return this.stateManager.getState() === Re.NOT_STARTED;
  }, w.prototype.isScanning = function() {
    return this.stateManager.getState() !== Re.NOT_STARTED;
  }, w.prototype.isStrictlyScanning = function() {
    return this.stateManager.getState() === Re.SCANNING;
  }, w.prototype.isPaused = function() {
    return this.stateManager.getState() === Re.PAUSED;
  }, w;
}(), Ui = function() {
  function w() {
  }
  return w.create = function() {
    return new ki(new Fi());
  }, w;
}(), Vi = /* @__PURE__ */ function() {
  var w = function(x, h) {
    return w = Object.setPrototypeOf || { __proto__: [] } instanceof Array && function(m, I) {
      m.__proto__ = I;
    } || function(m, I) {
      for (var g in I) Object.prototype.hasOwnProperty.call(I, g) && (m[g] = I[g]);
    }, w(x, h);
  };
  return function(x, h) {
    if (typeof h != "function" && h !== null)
      throw new TypeError("Class extends value " + String(h) + " is not a constructor or null");
    w(x, h);
    function m() {
      this.constructor = x;
    }
    x.prototype = h === null ? Object.create(h) : (m.prototype = h.prototype, new m());
  };
}(), ot = function(w) {
  Vi(x, w);
  function x() {
    return w !== null && w.apply(this, arguments) || this;
  }
  return x.DEFAULT_WIDTH = 300, x.DEFAULT_WIDTH_OFFSET = 2, x.FILE_SCAN_MIN_HEIGHT = 300, x.FILE_SCAN_HIDDEN_CANVAS_PADDING = 100, x.MIN_QR_BOX_SIZE = 50, x.SHADED_LEFT = 1, x.SHADED_RIGHT = 2, x.SHADED_TOP = 3, x.SHADED_BOTTOM = 4, x.SHADED_REGION_ELEMENT_ID = "qr-shaded-region", x.VERBOSE = !1, x.BORDER_SHADER_DEFAULT_COLOR = "#ffffff", x.BORDER_SHADER_MATCH_COLOR = "rgb(90, 193, 56)", x;
}(mi), Gi = function() {
  function w(x, h) {
    this.logger = h, this.fps = ot.SCAN_DEFAULT_FPS, x ? (x.fps && (this.fps = x.fps), this.disableFlip = x.disableFlip === !0, this.qrbox = x.qrbox, this.aspectRatio = x.aspectRatio, this.videoConstraints = x.videoConstraints) : this.disableFlip = ot.DEFAULT_DISABLE_FLIP;
  }
  return w.prototype.isMediaStreamConstraintsValid = function() {
    return this.videoConstraints ? Vr.isMediaStreamConstraintsValid(this.videoConstraints, this.logger) : (this.logger.logError("Empty videoConstraints", !0), !1);
  }, w.prototype.isShadedBoxEnabled = function() {
    return !bt(this.qrbox);
  }, w.create = function(x, h) {
    return new w(x, h);
  }, w;
}(), Ht = function() {
  function w(x, h) {
    if (this.element = null, this.canvasElement = null, this.scannerPausedUiElement = null, this.hasBorderShaders = null, this.borderShaders = null, this.qrMatch = null, this.renderedCamera = null, this.qrRegion = null, this.context = null, this.lastScanImageFile = null, this.isScanning = !1, !document.getElementById(x))
      throw "HTML Element with id=".concat(x, " not found");
    this.elementId = x, this.verbose = !1;
    var m;
    typeof h == "boolean" ? this.verbose = h === !0 : h && (m = h, this.verbose = m.verbose === !0, m.experimentalFeatures), this.logger = new pi(this.verbose), this.qrcode = new Ti(this.getSupportedFormats(h), this.getUseBarCodeDetectorIfSupported(m), this.verbose, this.logger), this.foreverScanTimeout, this.shouldScan = !0, this.stateManagerProxy = Ui.create();
  }
  return w.prototype.start = function(x, h, m, I) {
    var g = this;
    if (!x)
      throw "cameraIdOrConfig is required";
    if (!m || typeof m != "function")
      throw "qrCodeSuccessCallback is required and should be a function.";
    var T;
    I ? T = I : T = this.verbose ? this.logger.log : function() {
    };
    var M = Gi.create(h, this.logger);
    this.clearElement();
    var B = !1;
    M.videoConstraints && (M.isMediaStreamConstraintsValid() ? B = !0 : this.logger.logError("'videoConstraints' is not valid 'MediaStreamConstraints, it will be ignored.'", !0));
    var E = B, R = document.getElementById(this.elementId);
    R.clientWidth ? R.clientWidth : ot.DEFAULT_WIDTH, R.style.position = "relative", this.shouldScan = !0, this.element = R;
    var D = this, ne = this.stateManagerProxy.startTransition(Re.SCANNING);
    return new Promise(function(J, Be) {
      var re = E ? M.videoConstraints : D.createVideoConstraints(x);
      if (!re) {
        ne.cancel(), Be("videoConstraints should be defined");
        return;
      }
      var xt = {};
      (!E || M.aspectRatio) && (xt.aspectRatio = M.aspectRatio);
      var yt = {
        onRenderSurfaceReady: function(me, j) {
          D.setupUi(me, j, M), D.isScanning = !0, D.foreverScan(M, m, T);
        }
      };
      Bi.failIfNotSupported().then(function(me) {
        me.create(re).then(function(j) {
          return j.render(g.element, xt, yt).then(function(le) {
            D.renderedCamera = le, ne.execute(), J(null);
          }).catch(function(le) {
            ne.cancel(), Be(le);
          });
        }).catch(function(j) {
          ne.cancel(), Be(en.errorGettingUserMedia(j));
        });
      }).catch(function(me) {
        ne.cancel(), Be(en.cameraStreamingNotSupported());
      });
    });
  }, w.prototype.pause = function(x) {
    if (!this.stateManagerProxy.isStrictlyScanning())
      throw "Cannot pause, scanner is not scanning.";
    this.stateManagerProxy.directTransition(Re.PAUSED), this.showPausedState(), (bt(x) || x !== !0) && (x = !1), x && this.renderedCamera && this.renderedCamera.pause();
  }, w.prototype.resume = function() {
    if (!this.stateManagerProxy.isPaused())
      throw "Cannot result, scanner is not paused.";
    if (!this.renderedCamera)
      throw "renderedCamera doesn't exist while trying resume()";
    var x = this, h = function() {
      x.stateManagerProxy.directTransition(Re.SCANNING), x.hidePausedState();
    };
    if (!this.renderedCamera.isPaused()) {
      h();
      return;
    }
    this.renderedCamera.resume(function() {
      h();
    });
  }, w.prototype.getState = function() {
    return this.stateManagerProxy.getState();
  }, w.prototype.stop = function() {
    var x = this;
    if (!this.stateManagerProxy.isScanning())
      throw "Cannot stop, scanner is not running or paused.";
    var h = this.stateManagerProxy.startTransition(Re.NOT_STARTED);
    this.shouldScan = !1, this.foreverScanTimeout && clearTimeout(this.foreverScanTimeout);
    var m = function() {
      if (x.element) {
        var g = document.getElementById(ot.SHADED_REGION_ELEMENT_ID);
        g && x.element.removeChild(g);
      }
    }, I = this;
    return this.renderedCamera.close().then(function() {
      return I.renderedCamera = null, I.element && (I.element.removeChild(I.canvasElement), I.canvasElement = null), m(), I.qrRegion && (I.qrRegion = null), I.context && (I.context = null), h.execute(), I.hidePausedState(), I.isScanning = !1, Promise.resolve();
    });
  }, w.prototype.scanFile = function(x, h) {
    return this.scanFileV2(x, h).then(function(m) {
      return m.decodedText;
    });
  }, w.prototype.scanFileV2 = function(x, h) {
    var m = this;
    if (!x || !(x instanceof File))
      throw "imageFile argument is mandatory and should be instance of File. Use 'event.target.files[0]'.";
    if (bt(h) && (h = !0), !this.stateManagerProxy.canScanFile())
      throw "Cannot start file scan - ongoing camera scan";
    return new Promise(function(I, g) {
      m.possiblyCloseLastScanImageFile(), m.clearElement(), m.lastScanImageFile = URL.createObjectURL(x);
      var T = new Image();
      T.onload = function() {
        var M = T.width, B = T.height, E = document.getElementById(m.elementId), R = E.clientWidth ? E.clientWidth : ot.DEFAULT_WIDTH, D = Math.max(E.clientHeight ? E.clientHeight : B, ot.FILE_SCAN_MIN_HEIGHT), ne = m.computeCanvasDrawConfig(M, B, R, D);
        if (h) {
          var J = m.createCanvasElement(R, D, "qr-canvas-visible");
          J.style.display = "inline-block", E.appendChild(J);
          var Be = J.getContext("2d");
          if (!Be)
            throw "Unable to get 2d context from canvas";
          Be.canvas.width = R, Be.canvas.height = D, Be.drawImage(T, 0, 0, M, B, ne.x, ne.y, ne.width, ne.height);
        }
        var re = ot.FILE_SCAN_HIDDEN_CANVAS_PADDING, xt = Math.max(T.width, ne.width), yt = Math.max(T.height, ne.height), me = xt + 2 * re, j = yt + 2 * re, le = m.createCanvasElement(me, j);
        E.appendChild(le);
        var St = le.getContext("2d");
        if (!St)
          throw "Unable to get 2d context from canvas";
        St.canvas.width = me, St.canvas.height = j, St.drawImage(T, 0, 0, M, B, re, re, xt, yt);
        try {
          m.qrcode.decodeRobustlyAsync(le).then(function(xe) {
            I(_r.createFromQrcodeResult(xe));
          }).catch(g);
        } catch (xe) {
          g("QR code parse error, error = ".concat(xe));
        }
      }, T.onerror = g, T.onabort = g, T.onstalled = g, T.onsuspend = g, T.src = URL.createObjectURL(x);
    });
  }, w.prototype.clear = function() {
    this.clearElement();
  }, w.getCameras = function() {
    return Pi.retrieve();
  }, w.prototype.getRunningTrackCapabilities = function() {
    return this.getRenderedCameraOrFail().getRunningTrackCapabilities();
  }, w.prototype.getRunningTrackSettings = function() {
    return this.getRenderedCameraOrFail().getRunningTrackSettings();
  }, w.prototype.getRunningTrackCameraCapabilities = function() {
    return this.getRenderedCameraOrFail().getCapabilities();
  }, w.prototype.applyVideoConstraints = function(x) {
    if (x) {
      if (!Vr.isMediaStreamConstraintsValid(x, this.logger))
        throw "invalid videoConstaints passed, check logs for more details";
    } else throw "videoConstaints is required argument.";
    return this.getRenderedCameraOrFail().applyVideoConstraints(x);
  }, w.prototype.getRenderedCameraOrFail = function() {
    if (this.renderedCamera == null)
      throw "Scanning is not in running state, call this API only when QR code scanning using camera is in running state.";
    return this.renderedCamera;
  }, w.prototype.getSupportedFormats = function(x) {
    var h = [
      H.QR_CODE,
      H.AZTEC,
      H.CODABAR,
      H.CODE_39,
      H.CODE_93,
      H.CODE_128,
      H.DATA_MATRIX,
      H.MAXICODE,
      H.ITF,
      H.EAN_13,
      H.EAN_8,
      H.PDF_417,
      H.RSS_14,
      H.RSS_EXPANDED,
      H.UPC_A,
      H.UPC_E,
      H.UPC_EAN_EXTENSION
    ];
    if (!x || typeof x == "boolean" || !x.formatsToSupport)
      return h;
    if (!Array.isArray(x.formatsToSupport))
      throw "configOrVerbosityFlag.formatsToSupport should be undefined or an array.";
    if (x.formatsToSupport.length === 0)
      throw "Atleast 1 formatsToSupport is needed.";
    for (var m = [], I = 0, g = x.formatsToSupport; I < g.length; I++) {
      var T = g[I];
      Ci(T) ? m.push(T) : this.logger.warn("Invalid format: ".concat(T, " passed in config, ignoring."));
    }
    if (m.length === 0)
      throw "None of formatsToSupport match supported values.";
    return m;
  }, w.prototype.getUseBarCodeDetectorIfSupported = function(x) {
    if (bt(x))
      return !0;
    if (!bt(x.useBarCodeDetectorIfSupported))
      return x.useBarCodeDetectorIfSupported !== !1;
    if (bt(x.experimentalFeatures))
      return !0;
    var h = x.experimentalFeatures;
    return bt(h.useBarCodeDetectorIfSupported) ? !0 : h.useBarCodeDetectorIfSupported !== !1;
  }, w.prototype.validateQrboxSize = function(x, h, m) {
    var I = this, g = m.qrbox;
    this.validateQrboxConfig(g);
    var T = this.toQrdimensions(x, h, g), M = function(E) {
      if (E < ot.MIN_QR_BOX_SIZE)
        throw "minimum size of 'config.qrbox' dimension value is" + " ".concat(ot.MIN_QR_BOX_SIZE, "px.");
    }, B = function(E) {
      return E > x && (I.logger.warn("`qrbox.width` or `qrbox` is larger than the width of the root element. The width will be truncated to the width of root element."), E = x), E;
    };
    M(T.width), M(T.height), T.width = B(T.width);
  }, w.prototype.validateQrboxConfig = function(x) {
    if (typeof x != "number" && typeof x != "function" && (x.width === void 0 || x.height === void 0))
      throw "Invalid instance of QrDimensions passed for 'config.qrbox'. Both 'width' and 'height' should be set.";
  }, w.prototype.toQrdimensions = function(x, h, m) {
    if (typeof m == "number")
      return { width: m, height: m };
    if (typeof m == "function")
      try {
        return m(x, h);
      } catch (I) {
        throw new Error("qrbox config was passed as a function but it failed with unknown error" + I);
      }
    return m;
  }, w.prototype.setupUi = function(x, h, m) {
    m.isShadedBoxEnabled() && this.validateQrboxSize(x, h, m);
    var I = bt(m.qrbox) ? { width: x, height: h } : m.qrbox;
    this.validateQrboxConfig(I);
    var g = this.toQrdimensions(x, h, I);
    g.height > h && this.logger.warn("[Html5Qrcode] config.qrbox has height that isgreater than the height of the video stream. Shading will be ignored");
    var T = m.isShadedBoxEnabled() && g.height <= h, M = {
      x: 0,
      y: 0,
      width: x,
      height: h
    }, B = T ? this.getShadedRegionBounds(x, h, g) : M, E = this.createCanvasElement(B.width, B.height), R = { willReadFrequently: !0 }, D = E.getContext("2d", R);
    D.canvas.width = B.width, D.canvas.height = B.height, this.element.append(E), T && this.possiblyInsertShadingElement(this.element, x, h, g), this.createScannerPausedUiElement(this.element), this.qrRegion = B, this.context = D, this.canvasElement = E;
  }, w.prototype.createScannerPausedUiElement = function(x) {
    var h = document.createElement("div");
    h.innerText = en.scannerPaused(), h.style.display = "none", h.style.position = "absolute", h.style.top = "0px", h.style.zIndex = "1", h.style.background = "rgba(9, 9, 9, 0.46)", h.style.color = "#FFECEC", h.style.textAlign = "center", h.style.width = "100%", x.appendChild(h), this.scannerPausedUiElement = h;
  }, w.prototype.scanContext = function(x, h) {
    var m = this;
    return this.stateManagerProxy.isPaused() ? Promise.resolve(!1) : this.qrcode.decodeAsync(this.canvasElement).then(function(I) {
      return x(I.text, _r.createFromQrcodeResult(I)), m.possiblyUpdateShaders(!0), !0;
    }).catch(function(I) {
      m.possiblyUpdateShaders(!1);
      var g = en.codeParseError(I);
      return h(g, Ii.createFrom(g)), !1;
    });
  }, w.prototype.foreverScan = function(x, h, m) {
    var I = this;
    if (this.shouldScan && this.renderedCamera) {
      var g = this.renderedCamera.getSurface(), T = g.videoWidth / g.clientWidth, M = g.videoHeight / g.clientHeight;
      if (!this.qrRegion)
        throw "qrRegion undefined when localMediaStream is ready.";
      var B = this.qrRegion.width * T, E = this.qrRegion.height * M, R = this.qrRegion.x * T, D = this.qrRegion.y * M;
      this.context.drawImage(g, R, D, B, E, 0, 0, this.qrRegion.width, this.qrRegion.height);
      var ne = function() {
        I.foreverScanTimeout = setTimeout(function() {
          I.foreverScan(x, h, m);
        }, I.getTimeoutFps(x.fps));
      };
      this.scanContext(h, m).then(function(J) {
        !J && x.disableFlip !== !0 ? (I.context.translate(I.context.canvas.width, 0), I.context.scale(-1, 1), I.scanContext(h, m).finally(function() {
          ne();
        })) : ne();
      }).catch(function(J) {
        I.logger.logError("Error happend while scanning context", J), ne();
      });
    }
  }, w.prototype.createVideoConstraints = function(x) {
    if (typeof x == "string")
      return { deviceId: { exact: x } };
    if (typeof x == "object") {
      var h = "facingMode", m = "deviceId", I = { user: !0, environment: !0 }, g = "exact", T = function(Be) {
        if (Be in I)
          return !0;
        throw "config has invalid 'facingMode' value = " + "'".concat(Be, "'");
      }, M = Object.keys(x);
      if (M.length !== 1)
        throw "'cameraIdOrConfig' object should have exactly 1 key," + " if passed as an object, found ".concat(M.length, " keys");
      var B = Object.keys(x)[0];
      if (B !== h && B !== m)
        throw "Only '".concat(h, "' and '").concat(m, "' ") + " are supported for 'cameraIdOrConfig'";
      if (B === h) {
        var E = x.facingMode;
        if (typeof E == "string") {
          if (T(E))
            return { facingMode: E };
        } else if (typeof E == "object")
          if (g in E) {
            if (T(E["".concat(g)]))
              return {
                facingMode: {
                  exact: E["".concat(g)]
                }
              };
          } else
            throw "'facingMode' should be string or object with" + " ".concat(g, " as key.");
        else {
          var R = typeof E;
          throw "Invalid type of 'facingMode' = ".concat(R);
        }
      } else {
        var D = x.deviceId;
        if (typeof D == "string")
          return { deviceId: D };
        if (typeof D == "object") {
          if (g in D)
            return {
              deviceId: { exact: D["".concat(g)] }
            };
          throw "'deviceId' should be string or object with" + " ".concat(g, " as key.");
        } else {
          var ne = typeof D;
          throw "Invalid type of 'deviceId' = ".concat(ne);
        }
      }
    }
    var J = typeof x;
    throw "Invalid type of 'cameraIdOrConfig' = ".concat(J);
  }, w.prototype.computeCanvasDrawConfig = function(x, h, m, I) {
    if (x <= m && h <= I) {
      var g = (m - x) / 2, T = (I - h) / 2;
      return {
        x: g,
        y: T,
        width: x,
        height: h
      };
    } else {
      var M = x, B = h;
      return x > m && (h = m / x * h, x = m), h > I && (x = I / h * x, h = I), this.logger.log("Image downsampled from " + "".concat(M, "X").concat(B) + " to ".concat(x, "X").concat(h, ".")), this.computeCanvasDrawConfig(x, h, m, I);
    }
  }, w.prototype.clearElement = function() {
    if (this.stateManagerProxy.isScanning())
      throw "Cannot clear while scan is ongoing, close it first.";
    var x = document.getElementById(this.elementId);
    x && (x.innerHTML = "");
  }, w.prototype.possiblyUpdateShaders = function(x) {
    this.qrMatch !== x && (this.hasBorderShaders && this.borderShaders && this.borderShaders.length && this.borderShaders.forEach(function(h) {
      h.style.backgroundColor = x ? ot.BORDER_SHADER_MATCH_COLOR : ot.BORDER_SHADER_DEFAULT_COLOR;
    }), this.qrMatch = x);
  }, w.prototype.possiblyCloseLastScanImageFile = function() {
    this.lastScanImageFile && (URL.revokeObjectURL(this.lastScanImageFile), this.lastScanImageFile = null);
  }, w.prototype.createCanvasElement = function(x, h, m) {
    var I = x, g = h, T = document.createElement("canvas");
    return T.style.width = "".concat(I, "px"), T.style.height = "".concat(g, "px"), T.style.display = "none", T.id = bt(m) ? "qr-canvas" : m, T;
  }, w.prototype.getShadedRegionBounds = function(x, h, m) {
    if (m.width > x || m.height > h)
      throw "'config.qrbox' dimensions should not be greater than the dimensions of the root HTML element.";
    return {
      x: (x - m.width) / 2,
      y: (h - m.height) / 2,
      width: m.width,
      height: m.height
    };
  }, w.prototype.possiblyInsertShadingElement = function(x, h, m, I) {
    if (!(h - I.width < 1 || m - I.height < 1)) {
      var g = document.createElement("div");
      g.style.position = "absolute";
      var T = (h - I.width) / 2, M = (m - I.height) / 2;
      if (g.style.borderLeft = "".concat(T, "px solid rgba(0, 0, 0, 0.48)"), g.style.borderRight = "".concat(T, "px solid rgba(0, 0, 0, 0.48)"), g.style.borderTop = "".concat(M, "px solid rgba(0, 0, 0, 0.48)"), g.style.borderBottom = "".concat(M, "px solid rgba(0, 0, 0, 0.48)"), g.style.boxSizing = "border-box", g.style.top = "0px", g.style.bottom = "0px", g.style.left = "0px", g.style.right = "0px", g.id = "".concat(ot.SHADED_REGION_ELEMENT_ID), h - I.width < 11 || m - I.height < 11)
        this.hasBorderShaders = !1;
      else {
        var B = 5, E = 40;
        this.insertShaderBorders(g, E, B, -B, null, 0, !0), this.insertShaderBorders(g, E, B, -B, null, 0, !1), this.insertShaderBorders(g, E, B, null, -B, 0, !0), this.insertShaderBorders(g, E, B, null, -B, 0, !1), this.insertShaderBorders(g, B, E + B, -B, null, -B, !0), this.insertShaderBorders(g, B, E + B, null, -B, -B, !0), this.insertShaderBorders(g, B, E + B, -B, null, -B, !1), this.insertShaderBorders(g, B, E + B, null, -B, -B, !1), this.hasBorderShaders = !0;
      }
      x.append(g);
    }
  }, w.prototype.insertShaderBorders = function(x, h, m, I, g, T, M) {
    var B = document.createElement("div");
    B.style.position = "absolute", B.style.backgroundColor = ot.BORDER_SHADER_DEFAULT_COLOR, B.style.width = "".concat(h, "px"), B.style.height = "".concat(m, "px"), I !== null && (B.style.top = "".concat(I, "px")), g !== null && (B.style.bottom = "".concat(g, "px")), M ? B.style.left = "".concat(T, "px") : B.style.right = "".concat(T, "px"), this.borderShaders || (this.borderShaders = []), this.borderShaders.push(B), x.appendChild(B);
  }, w.prototype.showPausedState = function() {
    if (!this.scannerPausedUiElement)
      throw "[internal error] scanner paused UI element not found";
    this.scannerPausedUiElement.style.display = "block";
  }, w.prototype.hidePausedState = function() {
    if (!this.scannerPausedUiElement)
      throw "[internal error] scanner paused UI element not found";
    this.scannerPausedUiElement.style.display = "none";
  }, w.prototype.getTimeoutFps = function(x) {
    return 1e3 / x;
  }, w;
}(), Lr;
(function(w) {
  w[w.STATUS_DEFAULT = 0] = "STATUS_DEFAULT", w[w.STATUS_SUCCESS = 1] = "STATUS_SUCCESS", w[w.STATUS_WARNING = 2] = "STATUS_WARNING", w[w.STATUS_REQUESTING_PERMISSION = 3] = "STATUS_REQUESTING_PERMISSION";
})(Lr || (Lr = {}));
class Hi {
  constructor() {
    this.audioCtx = null;
  }
  getAudioContext() {
    if (!this.audioCtx && typeof window < "u" && (window.AudioContext || window.webkitAudioContext)) {
      const x = window.AudioContext || window.webkitAudioContext;
      this.audioCtx = new x();
    }
    return this.audioCtx;
  }
  playSuccessBeep(x = 880, h = 80) {
    try {
      const m = this.getAudioContext();
      if (!m) return;
      m.state === "suspended" && m.resume();
      const I = m.createOscillator(), g = m.createGain();
      I.type = "sine", I.frequency.setValueAtTime(x, m.currentTime), g.gain.setValueAtTime(0.15, m.currentTime), g.gain.exponentialRampToValueAtTime(1e-4, m.currentTime + h / 1e3), I.connect(g), g.connect(m.destination), I.start(), I.stop(m.currentTime + h / 1e3);
    } catch (m) {
      console.debug("Audio feedback error:", m);
    }
  }
  vibrate(x = 100) {
    try {
      typeof navigator < "u" && navigator.vibrate && navigator.vibrate(x);
    } catch (h) {
      console.debug("Haptic feedback error:", h);
    }
  }
  trigger(x = {}) {
    const h = x.sound !== !1, m = x.vibrate !== !1;
    h && this.playSuccessBeep(x.frequency || 880, x.duration || 80), m && this.vibrate(x.vibrateDuration || 100);
  }
}
const wn = new Hi();
function Bn({
  burstThresholdMs: w = 50,
  minBarcodeLength: x = 2,
  preventFormSubmit: h = !0,
  terminators: m = ["Enter", "Tab"],
  onScan: I = null,
  sound: g = !0,
  vibrate: T = !0
} = {}) {
  let M = "", B = 0, E = !1;
  return {
    handleKeyDown(R) {
      const D = Date.now(), ne = D - B;
      B = D;
      const J = m.includes(R.key);
      if (ne <= w ? E = !0 : ne > w * 3 && (M = "", E = !1), J) {
        if (E && M.length >= x) {
          h && (R.preventDefault(), R.stopPropagation());
          const Be = M.trim();
          return M = "", E = !1, wn.trigger({ sound: g, vibrate: T }), typeof I == "function" && I(Be), !0;
        }
        return M = "", E = !1, !1;
      }
      return R.key.length === 1 && !R.ctrlKey && !R.altKey && !R.metaKey && (M += R.key), !1;
    },
    reset() {
      M = "", E = !1, B = 0;
    },
    getBuffer() {
      return M;
    }
  };
}
function Pr({
  state: w = null,
  nextField: x = null,
  sound: h = !0,
  vibrate: m = !0,
  hardwareScanner: I = !0,
  burstThresholdMs: g = 50,
  fps: T = 15,
  qrbox: M = 250,
  preferRearCamera: B = !0
} = {}) {
  return {
    // State
    value: w,
    isModalOpen: !1,
    isScanning: !1,
    isLoading: !1,
    hasError: !1,
    errorMessage: "",
    devices: [],
    selectedDeviceId: null,
    html5Qrcode: null,
    scannerElementId: "",
    wedgeHandler: null,
    torchActive: !1,
    hasTorch: !1,
    init() {
      this.scannerElementId = `qr-reader-${this.$id("qr-reader")}`, I && (this.wedgeHandler = Bn({
        burstThresholdMs: g,
        sound: h,
        vibrate: m,
        onScan: (E) => {
          this.handleScanResult(E);
        }
      }), this.$el.addEventListener("keydown", (E) => {
        this.wedgeHandler.handleKeyDown(E);
      })), this.$watch("value", (E) => {
        this.$wire && this.$wire.set(this.getStatePath(), E);
      });
    },
    getStatePath() {
      return this.$el.getAttribute("wire:model") || this.$el.getAttribute("wire:model.defer") || this.$el.getAttribute("wire:model.live") || "";
    },
    openScannerModal() {
      this.isModalOpen = !0, this.hasError = !1, this.errorMessage = "", this.$nextTick(() => {
        this.loadCamerasAndStart();
      });
    },
    closeScannerModal() {
      this.stopScan().then(() => {
        this.isModalOpen = !1;
      });
    },
    async loadCamerasAndStart() {
      this.isLoading = !0, this.hasError = !1;
      try {
        const E = await Ht.getCameras();
        if (this.devices = E || [], !this.devices.length)
          throw new Error("No camera devices detected on this system.");
        this.selectedDeviceId = this.selectPreferredCamera(this.devices), this.isLoading = !1, await this.startScan();
      } catch (E) {
        this.isLoading = !1, this.hasError = !0, this.errorMessage = E.message || "Failed to access camera.";
      }
    },
    selectPreferredCamera(E) {
      var ne;
      if (!B || !E.length)
        return ((ne = E[0]) == null ? void 0 : ne.id) || null;
      const R = ["back", "rear", "environment", "camera 0", "facing back", "main"], D = E.find((J) => {
        const Be = (J.label || "").toLowerCase();
        return R.some((re) => Be.includes(re));
      });
      return D ? D.id : E[0].id;
    },
    async startScan() {
      if (!this.selectedDeviceId) return;
      this.isScanning && await this.stopScan(), this.html5Qrcode || (this.html5Qrcode = new Ht(this.scannerElementId));
      const E = {
        fps: T,
        qrbox: M,
        aspectRatio: 1
      };
      try {
        await this.html5Qrcode.start(
          this.selectedDeviceId,
          E,
          (R) => {
            this.handleScanResult(R), this.closeScannerModal();
          },
          () => {
          }
        ), this.isScanning = !0, this.checkTorchSupport();
      } catch (R) {
        this.hasError = !0, this.errorMessage = R.message || "Error starting camera scanner.";
      }
    },
    async stopScan() {
      if (this.html5Qrcode && this.isScanning)
        try {
          await this.html5Qrcode.stop();
        } catch (E) {
          console.debug("Scanner stop error:", E);
        } finally {
          this.isScanning = !1, this.torchActive = !1;
        }
    },
    async toggleTorch() {
      if (!(!this.html5Qrcode || !this.isScanning))
        try {
          const E = this.html5Qrcode.getRunningTrackCameraCapabilities();
          E && E.torchFeature().isSupported() && (this.torchActive = !this.torchActive, await E.torchFeature().apply(this.torchActive));
        } catch (E) {
          console.debug("Torch toggle error:", E);
        }
    },
    checkTorchSupport() {
      var E;
      try {
        const R = (E = this.html5Qrcode) == null ? void 0 : E.getRunningTrackCameraCapabilities();
        this.hasTorch = !!(R && R.torchFeature().isSupported());
      } catch {
        this.hasTorch = !1;
      }
    },
    handleScanResult(E) {
      const R = (E || "").trim();
      R && (this.value = R, wn.trigger({ sound: h, vibrate: m }), window.dispatchEvent(new CustomEvent("qr-scanned", {
        detail: {
          value: R,
          field: this.$el.getAttribute("data-field-name") || null,
          nextField: x
        }
      })), x && this.$nextTick(() => {
        this.advanceFocus(x);
      }));
    },
    advanceFocus(E) {
      const R = document.querySelector(`[data-field-name="${E}"] input, input[name="${E}"], #${E}, [wire\\:model*="${E}"]`);
      R && (R.focus(), typeof R.select == "function" && R.select());
    },
    scanFile(E) {
      var D;
      const R = (D = E.target.files) == null ? void 0 : D[0];
      R && (this.html5Qrcode || (this.html5Qrcode = new Ht(this.scannerElementId)), this.html5Qrcode.scanFile(R, !0).then((ne) => {
        this.handleScanResult(ne), this.closeScannerModal();
      }).catch((ne) => {
        this.hasError = !0, this.errorMessage = "No QR code found in selected image.";
      }));
    }
  };
}
function Fr({
  fields: w = [],
  sound: x = !0,
  vibrate: h = !0,
  hardwareScanner: m = !0,
  fps: I = 15,
  qrbox: g = 250
} = {}) {
  return {
    fields: w,
    // array of { key: string, label: string }
    currentFieldIndex: 0,
    results: {},
    isScanning: !1,
    isLoading: !1,
    hasError: !1,
    errorMessage: "",
    devices: [],
    selectedDeviceId: null,
    html5Qrcode: null,
    elementId: "",
    wedgeHandler: null,
    init() {
      this.elementId = `qr-sequence-${this.$id("qr-seq")}`, m && (this.wedgeHandler = Bn({
        sound: x,
        vibrate: h,
        onScan: (T) => {
          this.processScan(T);
        }
      }), window.addEventListener("keydown", (T) => {
        this.wedgeHandler.handleKeyDown(T);
      })), this.loadCameras();
    },
    getCurrentField() {
      return this.fields[this.currentFieldIndex] || null;
    },
    setCurrentField(T) {
      T >= 0 && T < this.fields.length && (this.currentFieldIndex = T);
    },
    async loadCameras() {
      this.isLoading = !0, this.hasError = !1;
      try {
        const T = await Ht.getCameras();
        this.devices = T || [], this.devices.length > 0 && (this.selectedDeviceId = this.devices[0].id), this.isLoading = !1;
      } catch {
        this.isLoading = !1, this.hasError = !0, this.errorMessage = "Camera access denied or unavailable.";
      }
    },
    async startScanner() {
      if (this.selectedDeviceId) {
        this.isScanning && await this.stopScanner(), this.html5Qrcode || (this.html5Qrcode = new Ht(this.elementId));
        try {
          await this.html5Qrcode.start(
            this.selectedDeviceId,
            { fps: I, qrbox: g },
            (T) => {
              this.processScan(T);
            },
            () => {
            }
          ), this.isScanning = !0;
        } catch {
          this.hasError = !0, this.errorMessage = "Failed to start camera feed.";
        }
      }
    },
    async stopScanner() {
      if (this.html5Qrcode && this.isScanning)
        try {
          await this.html5Qrcode.stop();
        } catch (T) {
          console.debug("Error stopping sequence scanner:", T);
        } finally {
          this.isScanning = !1;
        }
    },
    processScan(T) {
      const M = this.getCurrentField();
      if (!M) return;
      const B = T.trim();
      B && (this.results[M.key] = B, wn.trigger({ sound: x, vibrate: h }), this.$wire && this.$wire.set(`data.${M.key}`, B), window.dispatchEvent(new CustomEvent("qr-sequence-step", {
        detail: {
          field: M.key,
          value: B,
          index: this.currentFieldIndex
        }
      })), this.currentFieldIndex < this.fields.length - 1 ? this.currentFieldIndex++ : window.dispatchEvent(new CustomEvent("qr-sequence-completed", {
        detail: { results: { ...this.results } }
      })));
    },
    resetSequence() {
      this.currentFieldIndex = 0, this.results = {};
    }
  };
}
function kr({
  allowDuplicates: w = !1,
  sound: x = !0,
  vibrate: h = !0,
  hardwareScanner: m = !0,
  fps: I = 15,
  qrbox: g = 250,
  delayBetweenScansMs: T = 1200
} = {}) {
  return {
    items: [],
    scannedSet: /* @__PURE__ */ new Set(),
    isScanning: !1,
    isProcessing: !1,
    isLoading: !1,
    hasError: !1,
    errorMessage: "",
    devices: [],
    selectedDeviceId: null,
    html5Qrcode: null,
    elementId: "",
    wedgeHandler: null,
    init() {
      this.elementId = `qr-collector-${this.$id("qr-col")}`, m && (this.wedgeHandler = Bn({
        sound: x,
        vibrate: h,
        onScan: (M) => {
          this.handleDetectedCode(M);
        }
      }), window.addEventListener("keydown", (M) => {
        this.wedgeHandler.handleKeyDown(M);
      })), this.loadCameras();
    },
    async loadCameras() {
      this.isLoading = !0;
      try {
        const M = await Ht.getCameras();
        this.devices = M || [], this.devices.length > 0 && (this.selectedDeviceId = this.devices[0].id), this.isLoading = !1;
      } catch {
        this.isLoading = !1, this.hasError = !0, this.errorMessage = "Camera access unavailable.";
      }
    },
    async startCollector() {
      if (this.selectedDeviceId) {
        this.isScanning && await this.stopCollector(), this.html5Qrcode || (this.html5Qrcode = new Ht(this.elementId));
        try {
          await this.html5Qrcode.start(
            this.selectedDeviceId,
            { fps: I, qrbox: g },
            (M) => {
              this.handleDetectedCode(M);
            },
            () => {
            }
          ), this.isScanning = !0;
        } catch {
          this.hasError = !0, this.errorMessage = "Failed to start camera.";
        }
      }
    },
    async stopCollector() {
      if (this.html5Qrcode && this.isScanning)
        try {
          await this.html5Qrcode.stop();
        } catch (M) {
          console.debug("Error stopping collector:", M);
        } finally {
          this.isScanning = !1;
        }
    },
    handleDetectedCode(M) {
      const B = (M || "").trim();
      !B || this.isProcessing || !w && this.scannedSet.has(B) || (this.isProcessing = !0, this.scannedSet.add(B), this.items.unshift({
        code: B,
        scanned_at: (/* @__PURE__ */ new Date()).toLocaleTimeString()
      }), wn.trigger({ sound: x, vibrate: h }), this.$wire && typeof this.$wire.handleCollectorScan == "function" && this.$wire.handleCollectorScan(B), window.dispatchEvent(new CustomEvent("qr-collector-item-added", {
        detail: { code: B }
      })), setTimeout(() => {
        this.isProcessing = !1;
      }, T));
    },
    removeItem(M) {
      const B = this.items[M];
      B && (this.scannedSet.delete(B.code), this.items.splice(M, 1));
    },
    clearAll() {
      this.items = [], this.scannedSet.clear();
    }
  };
}
if (typeof window < "u") {
  window.FilamentQrCode = {
    qrScannerComponent: Pr,
    qrScanSequenceComponent: Fr,
    qrCollectorComponent: kr,
    createWedgeHandler: Bn,
    qrFeedback: wn
  };
  const w = () => {
    window.Alpine && (window.Alpine.data("qrScanner", Pr), window.Alpine.data("qrScanSequence", Fr), window.Alpine.data("qrCollector", kr));
  };
  window.Alpine ? w() : document.addEventListener("alpine:init", w);
}
export {
  Bn as createWedgeHandler,
  kr as qrCollectorComponent,
  wn as qrFeedback,
  Fr as qrScanSequenceComponent,
  Pr as qrScannerComponent
};
