import React, {useCallback, useRef, useState} from "react";
import {Swiper, SwiperSlide} from "swiper/react";
import 'swiper/css';
import "swiper/css/lazy"
import 'swiper/css/navigation';
import FileInfo from "./FileInfo";
import ArrowBackIosIcon from '@material-ui/icons/ArrowBackIos';
import ArrowForwardIosIcon from '@material-ui/icons/ArrowForwardIos';

import SwiperCore, {Lazy, Navigation} from 'swiper';

SwiperCore.use([Lazy, Navigation]);

function getCorrectIndex(pos, array) {
  let index = pos
  const length = array.length
  if (pos > length - 1) {
    index = 0
  } else if (pos < 0) {
    index = length - 1
  }
  return index
}

const Gallery = ({files}) => {
  console.log("hello")

  const [current, setCurrent] = useState(0)
  const prevRef = useRef(null);
  const nextRef = useRef(null);
  const multisliderPrevRef = useRef(null);
  const multisliderNextRef = useRef(null);
  const swiperRef = useRef(null)
  const multiSwiperRef = useRef(null)

  const handleSlideClick = useCallback((key) => {
    swiperRef.current.swiper.slideTo(key);
    multiSwiperRef.current.swiper.slideTo(key);
    setCurrent(key)
  }, [])

  const handleNavClick = useCallback((direction) => {
    let index = 0
    if ((current === 0 && direction === "prev") || (current === files.files.length - 1 && direction === "next")) {
      return
    }
    if (direction === "next") {
      index = getCorrectIndex(current + 1, files?.files)
      multiSwiperRef?.current?.swiper.slideNext()
      setCurrent(index)
    } else {
      index = getCorrectIndex(current + 1, files?.files)
      multiSwiperRef?.current?.swiper.slidePrev()
      setCurrent(getCorrectIndex(current - 1, files?.files))
    }
  }, [current])

  return (
    <>
      <Swiper
        navigation={{
          prevEl: prevRef.current,
          nextEl: nextRef.current,
        }}
        onBeforeInit={(swiper) => {
          swiper.params.navigation.prevEl = prevRef.current
          swiper.params.navigation.nextEl = nextRef.current
        }}
        allowTouchMove={true}
        breakpoints={{
          "768": {
            "allowTouchMove": false
          }
        }}
        spaceBetween={50}
        slidesPerView={1}
        ref={swiperRef}
        className="ecl-gallery__main-slides"
      >
        {files?.files.map((file, key) => <SwiperSlide className={"ecl-gallery__main-slides__slide"} key={key}> <img
          className="swiper-lazy" src={file.image.src} alt={file.image.alt}/> </SwiperSlide>)}
        <div className={"ecl-gallery__action ecl-gallery__action__back"} ref={prevRef}
             onClick={() => handleNavClick("prev")}><ArrowBackIosIcon/></div>
        <div className={"ecl-gallery__action ecl-gallery__action__forward"} ref={nextRef}
             onClick={() => handleNavClick("next")}><ArrowForwardIosIcon/></div>
      </Swiper>

      <Swiper
        navigation={{
          prevEl: multisliderPrevRef.current,
          nextEl: multisliderNextRef.current,
        }}
        onBeforeInit={(swiper) => {
          swiper.params.navigation.prevEl = multisliderPrevRef.current
          swiper.params.navigation.nextEl = multisliderNextRef.current
        }}
        centeredSlides={true}
        spaceBetween={10}
        slidesPerView={3}
        ref={multiSwiperRef}
        allowTouchMove={true}
        breakpoints={{
          "640": {
            "slidesPerView": 4,
            "spaceBetween": 20
          },
          "768": {
            "allowTouchMove": false
          }
        }}
        className="ecl-gallery__main-slides ecl-gallery__main-slides--multislider"
      >

        {files?.files.map((file, key) => (
          <SwiperSlide
            className={"ecl-gallery__main-slides__slide"}
            onClick={() => handleSlideClick(key)}
            key={key}
          >
            <img
              style={{objectFit: "cover", cursor:"pointer"}}
              className="swiper-lazy"
              title={file.image.alt}
              src={file.image.src}
              alt={file.image.alt}
            />
          </SwiperSlide>
        ))}
        <div className={"ecl-gallery__action ecl-gallery__action--multislider ecl-gallery__action--multislider__back"}
             ref={multisliderPrevRef}><ArrowBackIosIcon/></div>
        <div
          className={"ecl-gallery__action ecl-gallery__action--multislider ecl-gallery__action--multislider__forward"}
          ref={multisliderNextRef}><ArrowForwardIosIcon/></div>
      </Swiper>

      <FileInfo
        file={files?.files[getCorrectIndex(current, files?.files)]}
        position={getCorrectIndex(current, files?.files) + 1}
        length={files?.files.length}
      />
    </>
  )
};


export default Gallery;
