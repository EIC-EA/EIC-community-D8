import React from 'react';
import ReactDOM from 'react-dom';
import StreamVideo from './index';

const element = document.getElementById('react-stream-video-block');
if (element) {
  const video = element.dataset.videoTitle;

  ReactDOM.render(<StreamVideo video={video}/>, element);
}
