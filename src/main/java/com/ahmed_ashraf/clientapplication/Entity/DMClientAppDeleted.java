package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

import java.util.Date;

@Entity
@Table(name = "DM_CLIENTAPP_DELETED")
@Getter
@Setter
public class DMClientAppDeleted {

    @EmbeddedId
    private DMClientAppDeletedId id;

    @Column(name = "EMP_STATUS")
    private String emp_status;

    @Column(name = "OFF_BRANCH_CODE")
    private String offBranchCode;

    @Column(name = "OFF_CODE")
    private String offCode;

    @Column(name = "APPDATE")
    private String appDate;

    @Column(name = "APP_LASTUPDATE")
    private String appLastUpdate;

    @Column(name = "LASTUPDATE")
    private String lastUpdate;
}












/*
import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.util.Date;

@Entity
@Table(name = "DM_CLIENTAPP_DELETED")
@Getter
@Setter
public class DMClientAppDeleted {
    @Id
    @Column(name = "SERIAL")
    private Long serial;

    @Id
    @Column(name = "NATIONALNO")
    private String nationalno;

    @Column(name = "OFF_BRANCH_CODE")
    private String offBranchCode;

    @Column(name = "OFF_CODE")
    private String offCode;

    @Column(name = "APPDATE")
    private String appDate;

    @Column(name = "APP_LASTUPDATE")
    private String appLastUpdate;

    @Column(name = "LASTUPDATE")
    private Date lastUpdate; // Set to sysdate
}
*/