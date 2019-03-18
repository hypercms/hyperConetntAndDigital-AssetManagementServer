<?php
// file extentions used in getfileinfo function.
// declaration of global categorization of files.
// do not use upper case characters!
$hcms_ext = array();
$hcms_ext['cms'] = ".asp.aspx.cgi.css.dhtm.dhtml.dtd.htm.html.java.js.jsp.off.page.php.phtml.pl.shtm.shtml.xhtm.xhtml.xml.xsl";
$hcms_ext['audio'] = ".aac.ac3.aiff.apr.au.audio.dwd.flac.la.m4a.mid.mp2.mp3.mpc.oga.ogg.ots.pac.ra.rka.rm.swa.vox.voc.wav.wma.wv";
$hcms_ext['video'] = ".3g2.3gp.4xm.a64.aac.aaf.ac3.act.adf.adts.adx.aea.aiff.alaw.alsa.amr.anm.apc.ape.apr.asf.asf_stream.ass.au.audio.avi.avm2.avs.bethsoftvid.bfi.bin.bink.bit.bmv.c93.caf.cavsvideo.cdg.cdxl.crc.daud.dfa.dirac.dnxhd.dsicin.dts.dv.dv1394.dvd.dxa.dwd.ea.ea_cdata.eac3.f32be.f32le.f4v.f64be.f64le.fbdev.ffm.ffmetadata.film_cpk.filmstrip.flic.flv.framecrc.framemd5.g722.g723_1.g729.gsm.gxf.h261.h263.h264.hls.idcin.idf.iff.ilbc.image2.image2pipe.ingenient.ipmovie.ipod.ismv.iss.iv8.ivf.jack.jacosub.jv.la.latm.lavfi.libcdio.libdc1394.lmlm4.loas.lxf.m4a.m4b.m4p.m4r.m4v.matroska.md5.mgsts.microdvd.mid.mj2.mjpeg.mkv.mlp.mm.mmf.mov.mp4.mp4v.mpc.mpc8.mpeg.mpg.mpeg1video.mpeg2video.mpegts.mpegtsraw.mpegvideo.mpjpeg.msnwctcp.mts.mtv.mulaw.mvi.mxf.mxf_d10.mxg.nc.nsv.null.nut.nuv.oga.ogg.ogm.ogv.oma.oss.ots.pac.paf.pmp.psp.psxstr.pva.qcp.qt.qtl.r3d.ra.ram.rawvideo.rcv.realtext.rka.rl2.rm.roq.rpl.rso.rtp.rtsp.s16be.s16le.s24be.s24le.s32be.s32le.s8.sami.sap.sbg.sdl.sdp.segment.shn.siff.smjpeg.smk.smush.sol.sox.spdif.subviewer.svcd.swa.swf.thp.tiertexseq.tmv.truehd.tta.tty.txd.u16be.u16le.u24be.u24le.u32be.u32le.u8.vc1.vc1test.vcd.video.vmd.vob.voc.vox.vp8.vqf.w64.wav.wc3movie.webm.webvtt.wma.wmv.wsaud.wsvqa.wtv.wv.x11grab.xa.xbin.xmv.xvid.xwma.yop.yuv4mpegpipe";
$hcms_ext['rawvideo'] = ".crm";
$hcms_ext['cleartxt'] = ".csv.log.srt.txt"; 
$hcms_ext['bintxt'] = ".abw.afp.ans.asc.cwk.dat.doc.docx.docm.dot.dotx.fodg.fodp.fods.fodt.mdb.mcw.odt.ods.odp.odb.odg.odf.pdf.ppt.pptx.potx.pps.ppsx.ppsm.ppst.rtf.sdw.stw.sxw.tex.wpd.wps.wpt.wri.xls.xlsx.xlst.xslm"; 
$hcms_ext['binary'] = ".exe.dll.jar";
$hcms_ext['flash'] = ".swf.dcr.fla";
$hcms_ext['image'] = ".ai.aai.act.art.artb.arw.avs.bmp.bmp2.bmp3.cals.cdr.cgm.cin.cit.cmyk.cmyka.cpt.cr2.crw.cur.cut.dcm.dcr.dcx.dib.djvu.dng.dpx.emf.epdf.epi.eps.eps2.eps3.epsf.epsi.ept.exr.fax.fig.fits.fpx.gif.gplt.gray.hdr.hpgl.hrz.ico.idml.indd.info.inline.jbig.jng.jp2.jpc.jpe.jpg.jpeg.jxr.man.mat.miff.mono.mng.mpc.mpr.mrw.msl.mtv.mvg.nef.orf.otb.p7.palm.pam.clipboard.pbm.pcd.pcds.pcl.pcx.pdb.pef.pfa.pfb.pfm.pgm.picon.pict.pix.pjpeg.png.png8.png00.png24.png32.png48.png64.pnm.ppm.ps.ps2.ps3.psb.psd.psp.ptif.pwp.pxr.rad.raf.raw.rgb.rgba.rla.rle.sct.sfw.sgi.shtml.sid.mrsid.sparse-color.sun.svg.tga.tif.tiff.tim.ttf.uil.uyvy.vicar.viff.wbmp.wdp.webp.wmf.wpg.x.xbm.xcf.xpm.xwd.x3f.ycbcr.ycbcra.yuv";
$hcms_ext['rawimage'] = ".arw.cr2.crw.dcr.mrw.nef.orf.uyvy";
$hcms_ext['vectorimage'] = ".abc.ac5.ac6.af2.af3.afdesign.ai.art.artb.asy.awg.cag.ccx.cdd.cddz.cdlx.cdmm.cdmt.cdmtz.cdmz.cdr.cds.cdsx.cdt.cdtx.cdx.cdx.cgm.cil.clarify.cmx.cnv.cor.csy.cv5.cvg.cvi.cvs.cvx.cwt.cxf.dcs.ddrw.ded.design.dhs.dia.dpp.dpr.dpx.drawing.drawit.drw.drw.dsf.dxb.egc.emf.emz.ep.eps.eps2.eps3.epsf.epsi.esc.ezdraw.fh10.fh11.fh3.fh4.fh5.fh6.fh7.fh8.fh9.fhd.fif.fig.fmv.fs.ft10.ft11.ft7.ft8.ft9.ftn.fxg.gdraw.gem.gks.glox.gls.graffle.gsd.gstencil.gtemplate.gvdesign.hgl.hpg.hpgl.hpl.idea.igt.igx.imd.ink.ink.jsl.lmk.mgc.mgcb.mgmf.mgmt.mgmx.mgs.mgtx.mmat.mp.mvg.nap.odg.otg.ovp.ovr.pat.pcs.pd.pen.pfd.pfv.pl.plt.plt.pmg.pobj.ps.psid.pws.qcc.rdl.scv.sda.sk1.sk2.sketch.slddrt.smf.snagitstamps.snagstyles.ssk.std.stn.svf.svg.svgz.sxd.tlc.tne.tpl.ufr.vbr.vec.vml.vsd.vsdm.vsdx.vst.vstm.vstx.wmf.wmz.wpg.wpi.xar.xmind.xmmap.xpr.yal.zgm";
$hcms_ext['compressed'] = "ace.gz.alz.at3.arc.arj.big.bkf.bz2.cab.cpt.daa.deb.dmg.eea.gho.ghs.gzip.jar.lbr.lqr.lzh.lzo.lzx.par.pk4.rar.sea.sit.tar.tgz.tib.zip";
$hcms_ext['font'] = ".afm.dfont.otf.pfa.pfb.pfm.ttc.ttf";
$hcms_ext['template'] = ".tpl";
?>