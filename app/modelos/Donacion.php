<?php
class Donacion {
    var $id;
    var $id_usuario;
    var $id_camp;
    var $id_categoria_insumos;
    var $id_sub_categoria_insumos;
    var $cantidad;
    var $fecha_hora;

    function __construct($id_usuario, $id_camp, $cantidad, $id_categoria_insumos = null, $id_sub_categoria_insumos = null, $fecha_hora = null) {
        $this->id_usuario = $id_usuario;
        $this->id_camp = $id_camp;
        $this->cantidad = $cantidad;
        $this->id_categoria_insumos = $id_categoria_insumos;
        $this->id_sub_categoria_insumos = $id_sub_categoria_insumos;
        $this->fecha_hora = $fecha_hora;
    }

    function setId($id) {
        $this->id = $id;
    }
    function getId() {
        return $this->id;
    }
    function setIdUsuario($id_usuario) {
        $this->id_usuario = $id_usuario;
    }
    function getIdUsuario() {
        return $this->id_usuario;
    }
    function setIdCamp($id_camp) {
        $this->id_camp = $id_camp;
    }
    function getIdCamp()
    {
        return $this->id_camp;
    }
    function setIdCategoriaInsumos($id_categoria_insumos) {
        $this->id_categoria_insumos = $id_categoria_insumos;
    }
    function getIdCategoriaInsumos() {
        return $this->id_categoria_insumos;
    }
    function setIdSubCategoriaInsumos($id_sub_categoria_insumos) {
        $this->id_sub_categoria_insumos = $id_sub_categoria_insumos;
    }
    function getIdSubCategoriaInsumos() {
        return $this->id_sub_categoria_insumos;
    }
    function setCantidad($cantidad) {
        $this->cantidad = $cantidad;
    }
    function getCantidad() {
        return $this->cantidad;
    }
    function setFechaHora($fecha_hora) {
        $this->fecha_hora = $fecha_hora;
    }
    function getFechaHora() {
        return $this->fecha_hora;
    }
}
?>